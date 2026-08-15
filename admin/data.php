<?php

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| CARDS DO DASHBOARD
|--------------------------------------------------------------------------
*/

/* Valor pago */
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM purchases
    WHERE status = 'paid'
");

$valorPago = (float) $stmt->fetchColumn();


/* Valor pendente */
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM purchases
    WHERE status = 'pending'
");

$valorPendente = (float) $stmt->fetchColumn();


/* Total de pessoas */
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM people
    WHERE active = 1
");

$totalPessoas = (int) $stmt->fetchColumn();


/* Total de compras */
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM purchases
");

$totalCompras = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| PAGO
|--------------------------------------------------------------------------
|
| Pessoas que possuem pelo menos uma compra paga.
|
*/

$stmt = $pdo->query("
    SELECT
        pe.id,
        pe.name,
        t.name AS team_name,

        SUM(p.total) AS total,

        MAX(p.paid_at) AS paid_at

    FROM people pe

    INNER JOIN teams t
        ON t.id = pe.team_id

    INNER JOIN purchases p
        ON p.person_id = pe.id

    WHERE
        p.status = 'paid'
        AND pe.active = 1

    GROUP BY
        pe.id,
        pe.name,
        t.name

    ORDER BY
        pe.name
");

$pagos =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| DEVENDO
|--------------------------------------------------------------------------
|
| Pessoas que possuem pelo menos uma compra pendente.
|
| Cada pessoa aparece apenas uma vez.
| O total mostra tudo que ela deve.
|
*/

$stmt = $pdo->query("
    SELECT
        pe.id,
        pe.name,
        t.name AS team_name,

        SUM(p.total) AS total

    FROM people pe

    INNER JOIN teams t
        ON t.id = pe.team_id

    INNER JOIN purchases p
        ON p.person_id = pe.id

    WHERE
        p.status = 'pending'
        AND pe.active = 1

    GROUP BY
        pe.id,
        pe.name,
        t.name

    ORDER BY
        total DESC,
        pe.name
");

$devendo =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| NÃO UTILIZADO
|--------------------------------------------------------------------------
|
| Pessoas ativas que nunca fizeram nenhuma compra.
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
    $stmt->fetchAll(PDO::FETCH_ASSOC);


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