# Plano: usuários e permissões

**Situação:** planejado, ainda não implementado.
**Anotado em:** 17/11/2026.

## O que se quer

Hoje todo mundo que faz login é administrador e pode tudo. A ideia é:

1. Um usuário **master** (o administrador de hoje), que pode mexer em tudo.
2. **Outros usuários**, cada um com seu login, para dar para saber
   **quem fez cada venda**.
3. Um perfil **visualizador**: navega por todas as abas e pode baixar o relatório,
   mas não cria, edita nem exclui nada.
4. Uma **tela "Usuários"** no admin para criar usuários, trocar senha, mudar o perfil
   e desativar — sem depender de SQL no phpMyAdmin.

## Perfis

| Perfil            | Pode                                                                 |
|-------------------|----------------------------------------------------------------------|
| **Administrador** | Tudo, inclusive a tela Usuários e a aba Juros.                       |
| **Vendedor**      | Lançar compras e registrar pagamentos. *(a confirmar, ver dúvidas)*  |
| **Visualizador**  | Só ver: todas as abas e o relatório (imprimir / salvar PDF).         |

## Dúvidas para decidir antes de começar

- **O que o vendedor pode fazer além de vender?** Marcar como pago? Repor estoque?
  Excluir compra? Sugestão: vender e receber pagamento, sem excluir e sem mexer em
  cadastros, estoque ou juros.
- **O visualizador vê tudo mesmo?** Inclusive quem está devendo e quanto.
- **Quem fez a venda aparece onde?** Sugestão: na tela da compra, na lista de compras
  e num bloco "Vendas por usuário" no relatório.
- **Compras antigas** ficam sem usuário ("não informado"), como aconteceu com a forma
  de pagamento.

## O que muda

### 1. Banco (um script novo em `database/`)

- `admins`: nova coluna `role ENUM('admin', 'seller', 'viewer') NOT NULL DEFAULT 'admin'`.
  Todos os usuários atuais continuam administradores.
- Para saber quem fez o quê, uma coluna `created_by` (id do usuário, pode ser nula) em:
  - `purchases` – quem lançou a venda
  - `purchase_payments` – quem recebeu o pagamento
  - `stock_movements` – quem repôs ou corrigiu o estoque

### 2. Login

- `admin/login.php`: guardar o perfil na sessão (`$_SESSION['admin_role']`), junto com
  `admin_id` e `admin_username`.
- Usuário desativado não entra (a coluna `active` já existe).

### 3. Bloqueio no servidor (a parte que protege de verdade)

Criar um arquivo `config/auth.php` com funções como `require_login()`,
`require_role('admin')` e `can('sell')`, e chamar em **todo lugar que grava**.
Esconder botão na tela não protege nada sozinho.

Pontos que gravam hoje:

| Arquivo                         | O que faz                                   |
|---------------------------------|---------------------------------------------|
| `api/purchases.php`             | Lança compra (e pagamento, se já paga)      |
| `api/purchase-payment.php`      | Paga uma compra                             |
| `api/purchase-status.php`       | Muda status (sem uso nas telas hoje)        |
| `api/delete-purchase.php`       | Exclui compra (sem uso nas telas hoje)      |
| `admin/purchases.php` (POST)    | Paga todas as pendentes da pessoa           |
| `admin/purchase-view.php` (POST)| Exclui compra                               |
| `admin/mark-person-paid.php`    | Paga todas (arquivo antigo, sem uso)        |
| `admin/product.php` (POST)      | Cria / edita produto                        |
| `admin/category.php` (POST)     | Cria / edita categoria                      |
| `admin/team.php` (POST)         | Cria / edita equipe                         |
| `admin/person.php` (POST)       | Cria / edita pessoa                         |
| `admin/stock.php` (POST)        | Repõe / corrige estoque                     |
| `admin/fees.php` (POST)         | Salva os juros                              |

Antes de começar, conferir se surgiu algum ponto novo (procurar por `INSERT`,
`UPDATE` e `DELETE` no código).

Aproveitar para colocar a validação CSRF nos que ainda não têm:
`api/purchase-status.php`, `api/delete-purchase.php` e `admin/mark-person-paid.php`.

### 4. Telas

- **Menu** (`admin/includes/header.php`): esconder "Nova compra" do visualizador;
  mostrar "Usuários" e "Juros" só para o administrador.
- **Botões de ação** escondidos para quem não pode: "+ Novo", "Editar", "+ Repor",
  "Corrigir quantidade", "Marcar como pago", "Excluir", "Salvar".
- **Telas de formulário** (`product.php`, `person.php`, `team.php`, `category.php`,
  `purchase.php`): quem não pode é mandado de volta para a lista.
- **Tela inicial de venda** (`index.php`): hoje só grava se houver admin logado.
  Mostrar uma mensagem clara para quem não pode vender.

### 5. Tela "Usuários" (nova, só para administrador)

- Lista de usuários com perfil e situação (ativo / inativo).
- Criar usuário: nome de usuário, senha, perfil.
- Editar: trocar senha, mudar perfil, ativar / desativar.
- Proteções: não deixar desativar ou rebaixar o **último** administrador ativo,
  nem o próprio usuário logado se desativar.
- Senhas sempre com `password_hash()` (o login já usa `password_hash`).

### 6. Quem fez a venda

- Gravar `created_by` ao lançar compra, registrar pagamento e mexer no estoque.
- Mostrar o usuário na tela da compra e no histórico do estoque.
- Relatório: bloco "Vendas por usuário" (quantidade e valor).

## Ordem sugerida

1. Script do banco + perfil na sessão + `config/auth.php`.
2. Bloqueio no servidor em todos os pontos que gravam.
3. Tela "Usuários".
4. Esconder botões e menus conforme o perfil.
5. Registrar e mostrar quem fez cada venda / pagamento / reposição.
6. Bloco "Vendas por usuário" no relatório.

As etapas 1 a 4 entregam os perfis funcionando. As etapas 5 e 6 são o
"quem fez a venda" e podem ficar para um segundo momento.
