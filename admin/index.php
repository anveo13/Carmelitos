<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stats = require __DIR__ . '/data.php';


/*
|--------------------------------------------------------------------------
| EQUIPES
|--------------------------------------------------------------------------
|
| Criamos a lista de equipes a partir dos próprios registros.
|
*/

$teams = [];

$allLists = [
    $stats['pagos'],
    $stats['devendo'],
    $stats['nao_utilizados']
];

foreach ($allLists as $list) {

    foreach ($list as $item) {

        if (!empty($item['team_name'])) {

            $teams[$item['team_name']] =
                $item['team_name'];

        }

    }

}

ksort($teams);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >


    <style>

        /* =========================================================
           CONTROLES
        ========================================================= */

        .status-tools {

            display: flex;

            gap: 12px;

            margin-top: 22px;

            flex-wrap: wrap;
        }


        .status-search {

            flex: 1;

            min-width: 220px;

            padding: 12px 15px;

            border:
                1px solid #dfe5e1;

            border-radius: 10px;

            font-size: 14px;

            outline: none;

            background: #fff;
        }


        .status-search:focus {

            border-color: #006b2d;

            box-shadow:
                0 0 0 3px
                rgba(0, 107, 45, 0.08);
        }


        .team-filter {

            min-width: 190px;

            padding: 12px 15px;

            border:
                1px solid #dfe5e1;

            border-radius: 10px;

            font-size: 14px;

            background: #fff;

            color: #173b28;

            outline: none;

            cursor: pointer;
        }


        .team-filter:focus {

            border-color: #006b2d;
        }



        /* =========================================================
           LISTA
        ========================================================= */

        .records-list {

            padding: 22px;

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 16px;
        }


        .record-card {

            display: flex;

            flex-direction: column;

            align-items: flex-start;

            justify-content: space-between;

            min-height: 150px;

            padding: 18px;

            border:
                1px solid #e6ebe8;

            border-radius: 14px;

            background: #fff;

            text-decoration: none;

            color: inherit;

            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease;
        }


        .record-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 6px 18px
                rgba(0, 0, 0, 0.07);
        }


        .record-card {
            cursor: pointer;
        }


        .record-card.is-open {
            box-shadow:
                0 8px 24px
                rgba(0, 0, 0, 0.09);
        }


        .record-value {
            font-size: 20px;
        }


        .record-count {
            margin-top: 5px;
            color: #7a857f;
            font-size: 12px;
        }


        .record-details {
            display: none;
            width: 100%;
            margin-top: 15px;
            padding-top: 14px;
            border-top: 1px solid #edf0ee;
        }


        .record-card.is-open .record-details {
            display: block;
        }


        .purchase-detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f1;
        }


        .purchase-detail:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }


        .purchase-detail-info {
            min-width: 0;
        }


        .purchase-detail-number {
            font-weight: 800;
            font-size: 13px;
            color: #4a4033;
        }


        .purchase-detail-date {
            margin-top: 3px;
            color: #9aa49e;
            font-size: 11px;
        }


        .purchase-detail-value {
            flex-shrink: 0;
            font-weight: 800;
            color: #e69c2f;
            font-size: 13px;
        }


        .purchase-detail-link {
            flex-shrink: 0;
            color: #e69c2f;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
        }


        .purchase-detail-link:hover {
            color: #b97816;
        }


        .record-info {

            min-width: 0;
        }


        .record-name {

            margin: 0;

            font-size: 16px;

            font-weight: 700;

            color: #123b25;
        }


        .record-team {

            margin-top: 5px;

            color: #718078;

            font-size: 13px;
        }


        .record-date {

            margin-top: 5px;

            color: #9aa49e;

            font-size: 12px;
        }


        .record-right {

            width: 100%;

            margin-top: auto;

            padding-top: 15px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            text-align: left;
        }


        .record-value {

            display: block;

            font-size: 17px;

            font-weight: 700;

            color: #123b25;
        }


        .record-status {

            display: inline-block;

            margin-top: 6px;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;
        }


        .record-status.paid {

            background: #e5f6eb;

            color: #087331;
        }


        .record-status.debt {

            background: #fff0e7;

            color: #b34b00;
        }


        .record-status.unused {

            background: #f0f1f1;

            color: #6c7470;
        }



        /* =========================================================
           CONTADOR
        ========================================================= */

        .records-header {

            padding: 18px 22px 0;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }


        .records-count {

            color: #7a857f;

            font-size: 13px;
        }



        /* =========================================================
           ESTADO VAZIO
        ========================================================= */

        .empty-state {

            padding: 60px 20px;
        }



        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 700px) {

            .status-tools {

                flex-direction: column;
            }


            .records-list {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

                padding: 15px;
            }


            .status-search,
            .team-filter {

                width: 100%;

                min-width: 0;
            }


            .record-card {

                padding: 15px;

                align-items:
                    flex-start;
            }


            .record-name {

                font-size: 15px;
            }


            .record-value {

                font-size: 15px;
            }


            .record-team {

                font-size: 12px;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="dashboard">


    <!-- =========================================================
         TÍTULO
    ========================================================== -->

    <div class="welcome">

        <div>

            <h1>
                Dashboard
            </h1>

            <p>
                Controle das compras e pagamentos.
            </p>

        </div>

    </div>



    <!-- =========================================================
         CARDS
    ========================================================== -->

    <section class="stats">


        <!-- VALOR PAGO -->

        <div class="stat-card">

            <div class="stat-icon paid">
                💰
            </div>

            <div>

                <span>
                    Valor Pago
                </span>

                <strong>

                    R$

                    <?= number_format(
                        $stats['valor_pago'],
                        2,
                        ',',
                        '.'
                    ) ?>

                </strong>

            </div>

        </div>



        <!-- VALOR PENDENTE -->

        <div class="stat-card">

            <div class="stat-icon pending">
                ⏳
            </div>

            <div>

                <span>
                    Valor Pendente
                </span>

                <strong>

                    R$

                    <?= number_format(
                        $stats['valor_pendente'],
                        2,
                        ',',
                        '.'
                    ) ?>

                </strong>

            </div>

        </div>



        <!-- PESSOAS -->

        <div class="stat-card">

            <div class="stat-icon people">
                👥
            </div>

            <div>

                <span>
                    Pessoas
                </span>

                <strong>
                    <?= $stats['total_pessoas'] ?>
                </strong>

            </div>

        </div>



        <!-- COMPRAS -->

        <div class="stat-card">

            <div class="stat-icon purchases">
                🛒
            </div>

            <div>

                <span>
                    Compras
                </span>

                <strong>
                    <?= $stats['total_compras'] ?>
                </strong>

            </div>

        </div>


    </section>



    <!-- =========================================================
         SITUAÇÃO
    ========================================================== -->

    <section class="status-section">


        <h2>
            Situação
        </h2>



        <!-- =====================================================
             ABAS
        ====================================================== -->

        <div class="status-tabs">


            <button
                class="status-tab active"
                data-status="paid"
            >
                ✅ Pago
            </button>


            <button
                class="status-tab"
                data-status="debt"
            >
                ⚠️ Devendo
            </button>


            <button
                class="status-tab"
                data-status="unused"
            >
                🚫 Não utilizado
            </button>


        </div>



        <!-- =====================================================
             BUSCA / EQUIPE
        ====================================================== -->

        <div class="status-tools">


            <input
                type="search"
                id="searchInput"
                class="status-search"
                placeholder="🔎 Buscar por nome..."
                autocomplete="off"
            >


            <select
                id="teamFilter"
                class="team-filter"
            >

                <option value="">
                    Todas as equipes
                </option>


                <?php foreach ($teams as $team): ?>

                    <option
                        value="<?= htmlspecialchars($team) ?>"
                    >

                        <?= htmlspecialchars($team) ?>

                    </option>

                <?php endforeach; ?>


            </select>


        </div>



        <!-- =====================================================
             CABEÇALHO DA LISTA
        ====================================================== -->

        <div class="records-header">

            <span>
                Registros
            </span>


            <span
                class="records-count"
                id="recordsCount"
            >
                0 registros
            </span>

        </div>



        <!-- =====================================================
             CONTEÚDO
        ====================================================== -->

        <div
            class="status-content"
            id="statusContent"
        >

        </div>


    </section>


</main>



<script>

/*
|--------------------------------------------------------------------------
| DADOS DO PHP
|--------------------------------------------------------------------------
|
| Transformamos os arrays PHP em objetos JavaScript.
|
*/

const records = {

    paid:

        <?= json_encode(
            $stats['pagos'],
            JSON_UNESCAPED_UNICODE
        ) ?>,


    debt:

        <?= json_encode(
            $stats['devendo'],
            JSON_UNESCAPED_UNICODE
        ) ?>,


    unused:

        <?= json_encode(
            $stats['nao_utilizados'],
            JSON_UNESCAPED_UNICODE
        ) ?>

};



/*
|--------------------------------------------------------------------------
| ELEMENTOS
|--------------------------------------------------------------------------
*/

const tabs =
    document.querySelectorAll(
        '.status-tab'
    );


const content =
    document.getElementById(
        'statusContent'
    );


const searchInput =
    document.getElementById(
        'searchInput'
    );


const teamFilter =
    document.getElementById(
        'teamFilter'
    );


const recordsCount =
    document.getElementById(
        'recordsCount'
    );



/*
|--------------------------------------------------------------------------
| ESTADO ATUAL
|--------------------------------------------------------------------------
*/

let currentStatus =
    'paid';



/*
|--------------------------------------------------------------------------
| FORMATAR MOEDA
|--------------------------------------------------------------------------
*/

function formatMoney(value) {

    return Number(value || 0)
        .toLocaleString(
            'pt-BR',
            {
                style: 'currency',
                currency: 'BRL'
            }
        );

}



/*
|--------------------------------------------------------------------------
| FORMATAR DATA
|--------------------------------------------------------------------------
*/

function formatDate(date) {

    if (!date) {

        return '';

    }


    const parsed =
        new Date(
            date.replace(
                ' ',
                'T'
            )
        );


    if (isNaN(parsed)) {

        return date;

    }


    return parsed.toLocaleDateString(
        'pt-BR'
    );

}



/*
|--------------------------------------------------------------------------
| NOME DO STATUS
|--------------------------------------------------------------------------
*/

function getStatusLabel(status) {

    const labels = {

        paid:
            'Pago',

        debt:
            'Devendo',

        unused:
            'Não utilizado'

    };


    return labels[status] || '';

}



/*
|--------------------------------------------------------------------------
| LINK DO REGISTRO
|--------------------------------------------------------------------------
*/

function getRecordLink(item) {

    /*
    |----------------------------------------------------------
    | PAGO / DEVENDO
    |----------------------------------------------------------
    |
    | Agora o ID recebido é o ID da compra.
    |
    */

    if (
        (
            currentStatus === 'paid' ||
            currentStatus === 'debt'
        )
        &&
        item.id
    ) {

        return `purchase-view.php?id=${item.id}`;

    }


    /*
    |----------------------------------------------------------
    | NÃO UTILIZADO
    |----------------------------------------------------------
    |
    | Nesse caso o ID continua sendo o ID da pessoa.
    |
    */

    if (
        currentStatus === 'unused' &&
        item.id
    ) {

        return `person.php?id=${item.id}`;

    }


    return '#';

}



/*
|--------------------------------------------------------------------------
| RENDERIZAR
|--------------------------------------------------------------------------
*/

function renderRecords() {


    let list =
        records[currentStatus] || [];


    const search =
        searchInput.value
            .trim()
            .toLowerCase();


    const team =
        teamFilter.value;


    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    list = list.filter(
        item => {

            const name =
                String(
                    item.name || ''
                ).toLowerCase();


            const itemTeam =
                String(
                    item.team_name || ''
                );


            const matchesName =
                !search ||
                name.includes(search);


            const matchesTeam =
                !team ||
                itemTeam === team;


            return (
                matchesName &&
                matchesTeam
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | AGRUPAR POR PESSOA
    |--------------------------------------------------------------------------
    |
    | Em vez de mostrar cada compra separadamente, agrupamos todas as
    | compras da mesma pessoa em um único card.
    |
    */

    const grouped = {};


    list.forEach(
        item => {

            /*
            | Pessoa + equipe formam a chave do agrupamento.
            */

            const key =
                `${item.person_id ?? item.id}::${item.team_name || ''}`;


            if (!grouped[key]) {

                grouped[key] = {

                    id:
                        item.person_id ?? item.id,

                    name:
                        item.name || '',

                    team_name:
                        item.team_name || 'Sem equipe',

                    total: 0,

                    count: 0,

                    purchases: []

                };

            }


            const value =
                Number(item.total || 0);


            /*
            | "Não utilizado" não possui compras.
            */

            if (currentStatus !== 'unused') {

                grouped[key].total += value;

                grouped[key].count++;

                grouped[key].purchases.push(item);

            }

        }
    );


    const groupedList =
        Object.values(grouped);


    /*
    |--------------------------------------------------------------------------
    | ORDENAÇÃO
    |--------------------------------------------------------------------------
    |
    | Pessoas com maior valor primeiro.
    |
    */

    groupedList.sort(
        (a, b) =>
            b.total - a.total
    );


    /*
    |--------------------------------------------------------------------------
    | CONTADOR
    |--------------------------------------------------------------------------
    */

    recordsCount.textContent =

        `${groupedList.length} ${
            groupedList.length === 1
                ? 'pessoa'
                : 'pessoas'
        }`;


    /*
    |--------------------------------------------------------------------------
    | NENHUM REGISTRO
    |--------------------------------------------------------------------------
    */

    if (groupedList.length === 0) {

        content.innerHTML = `

            <div class="empty-state">

                <div class="empty-icon">
                    📋
                </div>


                <h3>
                    Nenhum registro
                </h3>


                <p>
                    Nenhum registro encontrado
                    para este filtro.
                </p>

            </div>

        `;


        return;

    }


    /*
    |--------------------------------------------------------------------------
    | CARDS
    |--------------------------------------------------------------------------
    */

    const listHTML =

        groupedList.map(
            person => {

                const purchasesHTML =

                    person.purchases
                        .sort(
                            (a, b) =>
                                new Date(
                                    b.created_at || b.paid_at || 0
                                ) -
                                new Date(
                                    a.created_at || a.paid_at || 0
                                )
                        )
                        .map(
                            purchase => {

                                const purchaseDate =
                                    purchase.paid_at ||
                                    purchase.created_at ||
                                    '';


                                return `

                                    <div class="purchase-detail">

                                        <div class="purchase-detail-info">

                                            <div class="purchase-detail-number">

                                                Compra #${Number(
                                                    purchase.id || 0
                                                )}

                                            </div>


                                            ${
                                                purchaseDate
                                                    ? `
                                                        <div class="purchase-detail-date">

                                                            📅
                                                            ${formatDate(
                                                                purchaseDate
                                                            )}

                                                        </div>
                                                      `
                                                    : ''
                                            }

                                        </div>


                                        <span class="purchase-detail-value">

                                            ${formatMoney(
                                                purchase.total
                                            )}

                                        </span>


                                        <a
                                            href="purchase-view.php?id=${Number(
                                                purchase.id || 0
                                            )}"
                                            class="purchase-detail-link"
                                            onclick="event.stopPropagation();"
                                        >

                                            Ver

                                        </a>

                                    </div>

                                `;

                            }
                        )
                        .join('');


                const isUnused =
                    currentStatus === 'unused';


                return `

                    <div
                        class="record-card"
                        data-person-id="${Number(
                            person.id || 0
                        )}"
                    >

                        <div
                            class="record-info"
                            style="width: 100%;"
                        >

                            <h3 class="record-name">

                                ${escapeHTML(
                                    person.name
                                )}

                            </h3>


                            <div class="record-team">

                                👥

                                ${escapeHTML(
                                    person.team_name
                                )}

                            </div>


                            ${
                                isUnused
                                    ? `
                                        <div class="record-count">

                                            Ainda não realizou nenhuma compra

                                        </div>
                                      `
                                    : `
                                        <div class="record-count">

                                            ${person.count}
                                            ${
                                                person.count === 1
                                                    ? 'compra'
                                                    : 'compras'
                                            }

                                        </div>
                                      `
                            }

                        </div>


                        <div
                            class="record-right"
                            style="width: 100%;"
                        >

                            ${
                                !isUnused
                                    ? `
                                        <span class="record-value">

                                            ${formatMoney(
                                                person.total
                                            )}

                                        </span>
                                      `
                                    : ''
                            }


                            <span
                                class="
                                    record-status
                                    ${currentStatus}
                                "
                            >

                                ${getStatusLabel(
                                    currentStatus
                                )}

                            </span>

                        </div>


                        ${
                            !isUnused
                                ? `
                                    <div class="record-details">

                                        ${purchasesHTML}

                                    </div>
                                  `
                                : ''
                        }

                    </div>

                `;

            }
        )
        .join('');


    content.innerHTML = `

        <div class="records-list">

            ${listHTML}

        </div>

    `;


    /*
    |--------------------------------------------------------------------------
    | ABRIR / FECHAR DETALHES
    |--------------------------------------------------------------------------
    */

    content
        .querySelectorAll('.record-card')
        .forEach(
            card => {

                card.addEventListener(
                    'click',
                    event => {

                        if (
                            event.target.closest(
                                '.purchase-detail-link'
                            )
                        ) {

                            return;

                        }


                        card.classList.toggle(
                            'is-open'
                        );

                    }
                );

            }
        );

}



/*
|--------------------------------------------------------------------------
| SEGURANÇA
|--------------------------------------------------------------------------
|
| Evita colocar nomes vindos do banco diretamente
| como HTML.
|
*/

function escapeHTML(value) {

    return String(
        value ?? ''
    )

        .replace(
            /&/g,
            '&amp;'
        )

        .replace(
            /</g,
            '&lt;'
        )

        .replace(
            />/g,
            '&gt;'
        )

        .replace(
            /"/g,
            '&quot;'
        )

        .replace(
            /'/g,
            '&#039;'
        );

}



/*
|--------------------------------------------------------------------------
| CLICAR NAS ABAS
|--------------------------------------------------------------------------
*/

tabs.forEach(
    tab => {

        tab.addEventListener(
            'click',
            () => {


                tabs.forEach(
                    item => {

                        item.classList.remove(
                            'active'
                        );

                    }
                );


                tab.classList.add(
                    'active'
                );


                currentStatus =
                    tab.dataset.status;


                renderRecords();

            }
        );

    }
);



/*
|--------------------------------------------------------------------------
| BUSCA
|--------------------------------------------------------------------------
*/

searchInput.addEventListener(
    'input',
    renderRecords
);



/*
|--------------------------------------------------------------------------
| EQUIPE
|--------------------------------------------------------------------------
*/

teamFilter.addEventListener(
    'change',
    renderRecords
);



/*
|--------------------------------------------------------------------------
| PRIMEIRO CARREGAMENTO
|--------------------------------------------------------------------------
*/

renderRecords();

</script>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>