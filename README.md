# Carmelito's Minimercado

Sistema simples para controlar as vendas de um minimercado de encontro de igreja:
quem comprou, quem está devendo, o que foi vendido e o que sobrou no estoque.

Site: https://carmelitos.com.br/

## O que o sistema faz

- **Venda** – escolhe a equipe, a pessoa e os produtos, e lança a compra. Toda compra
  lançada pela tela inicial nasce como pendente (fiado).
- **Compras e pagamentos** – lista quem está devendo, marca como pago (uma compra ou
  todas as pendentes da pessoa) e exclui compras.
- **Formas de pagamento e juros** – cada pagamento registra a forma (Dinheiro, Pix,
  Crédito, Débito, Link de pagamento). Crédito, Débito e Link podem ter uma porcentagem
  de juros, calculada **uma vez por pagamento**, sobre o total pago.
- **Estoque** – cada produto tem uma quantidade. A venda dá baixa, excluir a compra
  devolve. Com 5 unidades ou menos o produto fica em vermelho. Reabastecimentos e
  correções ficam no histórico.
- **Relatório** – valores vendidos/recebidos/a receber, pagamentos por forma, juros,
  produtos mais vendidos, vendas por categoria e por dia, e o que sobrou no estoque.
  Pode ser impresso ou salvo em PDF.
- **Cadastros** – pessoas, equipes, produtos e categorias.

## Tecnologia

- PHP puro (sem framework), com PDO
- MySQL / MariaDB
- HTML, CSS e JavaScript sem bibliotecas
- Hospedagem com cPanel (deploy pelo `.cpanel.yml`)

## Estrutura

```
index.php               Tela de venda (lançar compra)
assets/                 CSS, JS e imagens da tela de venda
admin/                  Área administrativa (exige login)
  index.php             Dashboard
  purchase.php          Nova compra (pode já lançar como paga)
  purchases.php         Compras agrupadas por pessoa + marcar como pago
  purchase-view.php     Detalhe de uma compra
  people.php, person.php, teams.php, team.php
  products.php, product.php, categories.php, category.php
  stock.php             Estoque: repor e corrigir quantidades
  stock-history.php     Histórico de reabastecimentos e correções
  fees.php              Juros por forma de pagamento
  report.php            Relatório de vendas
  includes/             Cabeçalho, rodapé e modal de pagamento
api/                    Endpoints JSON usados pelas telas
config/
  database.php          Conexão com o banco (NÃO vai para o git)
  security.php          Token CSRF
  stock.php             Regras do estoque (limite de "acabando", movimentos)
  payments.php          Formas de pagamento, juros e registro de pagamentos
database/               Scripts SQL de cada mudança no banco
docs/                   Documentação e planos de melhorias futuras
```

## Rodando no computador

1. Ter PHP 8 e MySQL/MariaDB instalados.
2. Criar o banco `carmelitos` e importar um backup do banco do site
   (os arquivos de backup `.sql` não ficam no git).
3. Criar o arquivo `config/database.php` (modelo abaixo).
4. Na pasta do projeto:

   ```
   php -S localhost:8000
   ```

5. Abrir `http://localhost:8000` (venda) ou `http://localhost:8000/admin` (administração).

### Modelo do `config/database.php`

```php
<?php

$host = '127.0.0.1';
$dbname = 'carmelitos';
$username = 'SEU_USUARIO';
$password = 'SUA_SENHA';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados.');
}
```

## Banco de dados

Tabelas principais:

| Tabela              | O que guarda                                                    |
|---------------------|-----------------------------------------------------------------|
| `admins`            | Usuários que fazem login                                        |
| `teams`, `people`   | Equipes e pessoas                                               |
| `categories`, `products` | Categorias e produtos (com preço e `stock`)                |
| `purchases`         | Compras: pessoa, total, `status` (pending/paid), `payment_id`   |
| `purchase_items`    | Produtos de cada compra                                         |
| `stock_movements`   | Estoque inicial, reabastecimentos e correções                   |
| `payment_fees`      | Porcentagem de juros de Crédito, Débito e Link                  |
| `purchase_payments` | Cada pagamento: forma, valor das compras, juros e total cobrado |

A tabela `payments` é antiga e não é mais usada pelo código.

### Scripts de mudança (pasta `database/`)

Cada script é rodado **uma vez**, na ordem, no phpMyAdmin (aba SQL):

1. `add_stock.sql` – coluna `stock` nos produtos
2. `add_stock_movements.sql` – histórico do estoque
3. `add_payments.sql` – formas de pagamento e juros

## Publicando (deploy)

1. Se a mudança tiver script novo em `database/`, **rodar o SQL no phpMyAdmin primeiro**.
   Publicar o código antes do SQL causa erro 500 nas telas que usam a tabela/coluna nova.
2. Fazer o commit e o push.
3. No cPanel, em Git Version Control, usar **Deploy HEAD Commit**.

O `.cpanel.yml` copia apenas `admin/`, `api/`, `assets/`, `config/` e `index.php`
para o `public_html`. O `config/database.php` do servidor não é sobrescrito,
porque ele não está no git.

## Melhorias planejadas

- [Usuários e permissões](docs/usuarios-e-permissoes.md) – tela de usuários, perfis
  (administrador, vendedor, visualizador) e registro de quem fez cada venda.
