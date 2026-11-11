<?php

/*
|--------------------------------------------------------------------------
| ESTOQUE
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| LIMITE DE ESTOQUE BAIXO
|--------------------------------------------------------------------------
|
| Com esta quantidade ou menos, o produto aparece
| em vermelho como "acabando".
|
*/

const LOW_STOCK_LIMIT = 5;


/*
|--------------------------------------------------------------------------
| NÍVEL DO ESTOQUE
|--------------------------------------------------------------------------
|
| out = esgotado
| low = acabando
| ok  = normal
|
*/

function stock_level(
    int $stock
): string {

    if ($stock <= 0) {
        return 'out';
    }


    if ($stock <= LOW_STOCK_LIMIT) {
        return 'low';
    }


    return 'ok';
}


/*
|--------------------------------------------------------------------------
| TEXTO DO ESTOQUE
|--------------------------------------------------------------------------
*/

function stock_label(
    int $stock
): string {

    if ($stock <= 0) {
        return 'Esgotado';
    }


    return $stock . ' em estoque';
}


/*
|--------------------------------------------------------------------------
| TIPOS DE MOVIMENTO
|--------------------------------------------------------------------------
*/

const STOCK_MOVEMENT_LABELS = [

    'initial' => 'Estoque inicial',

    'restock' => 'Reabastecimento',

    'adjustment' => 'Correção'

];


/*
|--------------------------------------------------------------------------
| REGISTRAR MOVIMENTO
|--------------------------------------------------------------------------
|
| Deve ser chamado DEPOIS de atualizar products.stock,
| dentro da mesma transação: o estoque resultante é
| lido direto do produto.
|
*/

function stock_log_movement(
    PDO $pdo,
    int $productId,
    string $type,
    int $quantity
): void {

    $stmt = $pdo->prepare("
        INSERT INTO stock_movements
        (
            product_id,
            type,
            quantity,
            stock_after
        )

        SELECT
            id,
            ?,
            ?,
            stock

        FROM products

        WHERE id = ?
    ");

    $stmt->execute([
        $type,
        $quantity,
        $productId
    ]);
}
