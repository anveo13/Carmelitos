<?php

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| CARDS DO DASHBOARD
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| VALOR PAGO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM purchases
    WHERE status = 'paid'
");

$valorPago =
    (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| VALOR DEVENDO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM purchases
    WHERE status = 'pending'
");

$valorPendente =
    (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE PESSOAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM people
    WHERE active = 1
");

$totalPessoas =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE COMPRAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM purchases
");

$totalCompras =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| PAGOS
|--------------------------------------------------------------------------
|
| Cada registro representa uma compra específica.
|
| IMPORTANTE:
| O campo `id` abaixo é o ID da COMPRA,
| e não o ID da pessoa.
|
*/

$stmt = $pdo->query("
    SELECT

        p.id,

        p.person_id,

        p.total,

        p.status,

        p.created_at,

        p.paid_at,

        pe.name AS name,

        t.name AS team_name

    FROM purchases p

    INNER JOIN people pe
        ON pe.id = p.person_id

    INNER JOIN teams t
        ON t.id = pe.team_id

    WHERE
        p.status = 'paid'
        AND pe.active = 1

    ORDER BY
        p.paid_at DESC,
        p.id DESC
");

$pagos =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| DEVENDO
|--------------------------------------------------------------------------
|
| Cada registro representa uma compra específica
| que ainda não foi paga.
|
| O campo `id` é o ID da compra.
|
*/

$stmt = $pdo->query("
    SELECT

        p.id,

        p.person_id,

        p.total,

        p.status,

        p.created_at,

        p.paid_at,

        pe.name AS name,

        t.name AS team_name

    FROM purchases p

    INNER JOIN people pe
        ON pe.id = p.person_id

    INNER JOIN teams t
        ON t.id = pe.team_id

    WHERE
        p.status = 'pending'
        AND pe.active = 1

    ORDER BY
        p.created_at DESC,
        p.id DESC
");

$devendo =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| NÃO UTILIZADO
|--------------------------------------------------------------------------
|
| Pessoas ativas que nunca fizeram nenhuma compra.
|
| Aqui continuamos trabalhando com pessoa,
| porque não existe uma compra para abrir.
|
*/

$stmt = $pdo->query("
    SELECT

        pe.id,

        pe.name,

        t.name AS team_name

    FROM people pe

    INNER JOIN teams t
        ON t.id = pe.team_id

    LEFT JOIN purchases p
        ON p.person_id = pe.id

    WHERE
        p.id IS NULL
        AND pe.active = 1

    ORDER BY
        pe.name
");

$naoUtilizados =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| RETORNO
|--------------------------------------------------------------------------
*/

return [

    'valor_pago' =>
        $valorPago,

    'valor_pendente' =>
        $valorPendente,

    'total_pessoas' =>
        $totalPessoas,

    'total_compras' =>
        $totalCompras,

    'pagos' =>
        $pagos,

    'devendo' =>
        $devendo,

    'nao_utilizados' =>
        $naoUtilizados,

];