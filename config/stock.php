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
