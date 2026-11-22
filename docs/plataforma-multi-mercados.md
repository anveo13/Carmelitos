# Plano: Carmelito's como plataforma para mercados de encontro

**Situação:** planejado, nada implementado.
**Brief fechado em:** 22/11/2026.
**Substitui e amplia:** [usuarios-e-permissoes.md](usuarios-e-permissoes.md) (aquele plano vira a Etapa 1 deste).

> **Para retomar:** "Leia o docs/plataforma-multi-mercados.md e vamos começar pela Etapa 1."
> Antes de cada etapa, refazer o inventário do código: o projeto continua recebendo
> funcionalidades entre uma conversa e outra.

---

## 1. A ideia

Hoje o Carmelito's é um sistema de um dono só: um login, um mercado, um banco de dados.

A ideia é virar uma **plataforma para mercados cristãos de encontro** — o mercadinho que
funciona durante os 3 ou 4 dias de um encontro. Várias paróquias e vários encontros usam
o mesmo sistema, cada um no seu espaço, com a sua cara, sem ver os dados dos outros.

Não é para mudar o que o sistema faz. É para **replicar** o que já existe e separar quem
pode fazer o quê.

---

## 2. Hierarquia

```
Plataforma (dono)
└── Igreja / paróquia            /sao-jose-taguatinga
    └── Encontro                 /sao-jose-taguatinga/segueme
        └── Mercado (edição)     o mercado de cada realização do encontro
```

### 2.1 Igreja
A paróquia, igreja ou comunidade. **Todo encontro pertence a uma igreja**, mesmo quando
a paróquia não contratou a plataforma (encontro avulso).

- Tem nome, endereço (`slug`), logo e cores.
- O endereço é `nome-da-paroquia-cidade` (ex.: `sao-jose-taguatinga`) e é **único na
  plataforma**. A cidade faz parte do endereço para não haver colisão entre paróquias
  de mesmo nome.

### 2.2 Encontro
Um encontro da igreja: Segue-me, Renascer, etc.

- Endereço único **dentro da igreja**: `/sao-jose-taguatinga/segueme`.
- Herda logo e cores da igreja e pode trocar pelos seus.
- Dois encontros iguais na mesma paróquia:
  - em épocas diferentes → são **mercados** (edições) do mesmo encontro;
  - ao mesmo tempo (raro) → nomes diferentes: `segueme-jovens`, `segueme-casais`.

### 2.3 Mercado (edição)
O mercado de **uma realização** do encontro. É aqui que mora tudo o que é do dia a dia,
e tudo é **volátil** — cada mercado começa do zero:

- categorias e produtos (preço, custo, estoque);
- pessoas e equipes (quem compra);
- compras, itens, descontos e cortesias;
- pagamentos e juros;
- movimentos de estoque;
- vendedores vinculados.

O endereço do encontro sempre abre o **mercado atual** (o aberto; se não houver, o último).
Os anteriores ficam no histórico.

Ao criar um mercado novo, há um botão opcional **"copiar categorias e produtos do mercado
anterior"**: copia nome, categoria, preço e custo, com **estoque zerado**. Pessoas, equipes
e vendas nunca são copiadas.

---

## 3. Planos e cobrança

O endereço é sempre o mesmo (`/igreja/encontro`). O que muda entre paróquia e avulso é
**só o plano contratado**.

| | Plano paróquia | Encontro avulso |
|---|---|---|
| Quem contrata | A paróquia | O próprio encontro |
| Painel da paróquia | Sim, com todos os encontros | Não existe |
| Encontros | Quantos quiser | Só o que contratou |
| Pagamento | **Anual ou mensal**, à escolha | **Valor único por mercado** — cada mercado novo paga de novo |
| Quem administra | Admin da paróquia + admins dos encontros | Admin do encontro |

- **Paróquia que contrata depois:** o encontro avulso passa a ficar sob o painel dela.
  O endereço **não muda**, porque a igreja já estava nele.
- **Assinatura vencida** (paróquia) ou **mercado não pago** (avulso): fica **só para
  consulta** — relatórios e recebimento de pendências. Sem novas vendas, sem novos mercados.
- **No início, sem pagamento automático.** O dono recebe por fora (Pix) e marca no painel
  o plano e a validade de cada cliente. Cobrança automática é um projeto à parte.
- **Sem limites** de vendedores ou mercados por enquanto.
- Preços, regras comerciais e forma de venda: **a definir pelo dono** (ver seção 11).

---

## 4. Níveis de acesso

| Nível | Painel | O que pode |
|---|---|---|
| **Dono da plataforma** | Painel geral | Tudo. Vê todas as igrejas e encontros, entra em qualquer um, convida, define plano e validade, encerra, reabre, arquiva, **recupera arquivados** e apaga de verdade. |
| **Admin da paróquia** | Painel da paróquia | Tudo nos encontros da paróquia dele: cria encontros e mercados, convida admins, entra em cada um e mexe em tudo, encerra, **reabre** e **arquiva** mercados. |
| **Admin do encontro** | Dashboard do mercado (o de hoje) | Tudo dentro do encontro: produtos, preços, custos, estoque, categorias, pessoas, equipes, juros, descontos e cortesias, relatório, vendedores, personalização. **Encerra** o mercado, mas não reabre. |
| **Vendedor** | Tela de venda | Vende, lança compra já paga, recebe pagamento, vê quem está devendo, vê as próprias vendas e o total do mercado. |

O vendedor **não**: cadastra nem altera produtos, preços, estoque, categorias, pessoas,
equipes ou juros; dá desconto ou cortesia; exclui compras.

### Situação do mercado

| Situação | Vendedor | Admin do encontro | Admin da paróquia | Dono |
|---|---|---|---|---|
| **Aberto** | vende e recebe | tudo | tudo | tudo |
| **Encerrado** | sem acesso | recebe pendências e vê relatórios | idem + **reabrir** | tudo |
| **Arquivado** | sem acesso | não vê | não vê | vê e **recupera** |

- **Encerrar:** admin do encontro, admin da paróquia ou dono.
- **Reabrir:** admin da paróquia (nos encontros dela) ou dono. No avulso, só o dono.
- **"Apagar" é arquivar:** o mercado some para a paróquia, mas o dono recupera se foi
  engano. Apagar de verdade é só com o dono.
- Tudo isso é barrado **no servidor**, não só escondido na tela.

---

## 5. Contas, vínculos e convites

### 5.1 Conta
- A conta é **da plataforma**: uma pessoa, um login, para sempre.
- Login: **número do WhatsApp + senha**.
- **Trocar o número:** a própria pessoa troca no perfil, logada e confirmando a senha.
  Quem perdeu o acesso pede ao dono.
- **Esqueci a senha:** o admin (para as contas do espaço dele) ou o dono gera um
  **link de redefinição** e manda pelo WhatsApp. Validade curta, uso único.

### 5.2 Vínculo
Liga uma conta a um nível, com um papel:

| Papel | Vínculo com | Dura |
|---|---|---|
| Admin da paróquia | a igreja | enquanto não for removido |
| Admin do encontro | o encontro | vale para todos os mercados dele |
| Vendedor | o **mercado** | até o mercado encerrar |

- Quando o mercado encerra, o vínculo do vendedor **congela**: ele não vê nem mexe em mais
  nada dali.
- Chamado para vender em outro mercado (do mesmo encontro ou de outro), usa a **mesma
  conta** e ganha um vínculo novo — "reativa o perfil".
- Uma conta pode ter vários vínculos. Com mais de um ativo, escolhe onde entrar depois
  do login.
- **Remover um vendedor no meio do encontro:** ele só perde o acesso. As vendas continuam
  registradas no nome dele.
- Pode haver **mais de um admin** por paróquia e por encontro.

### 5.3 Convites
Sempre por **link**, enviado pelo WhatsApp:

- o dono convida o admin da paróquia ou o admin de um encontro avulso;
- o admin da paróquia convida admins dos encontros;
- o admin do encontro convida outros admins e os vendedores.

Quem convida **escolhe o papel** do convite (admin ou vendedor). O link tem validade e só
funciona uma vez. Quem já tem conta só aceita; quem não tem, cria.

### 5.4 Registro de quem fez
Toda venda, pagamento, reposição de estoque, exclusão, desconto e cortesia guarda **quem
fez**. Aparece nas telas ("vendido por Fulano") e no relatório ("vendas por vendedor").
Quando o dono ou o admin da paróquia age dentro de um encontro, fica registrado no nome dele.

---

## 6. Endereços

| Endereço | O que é |
|---|---|
| `carmelitos.com.br/` | Página de apresentação da plataforma, com botão de login |
| `/login` | Login único. Depois de entrar, leva ao lugar da pessoa (ou à escolha, se tiver mais de um vínculo) |
| `/convite/CODIGO` | Aceitar convite ou redefinir senha |
| `/painel` | Painel geral do dono |
| `/sao-jose-taguatinga` | Painel da paróquia (só existe no plano paróquia) |
| `/sao-jose-taguatinga/segueme` | O encontro: vendedor cai na venda, admin cai no dashboard |
| `/sao-jose-taguatinga/segueme/...` | As telas do mercado (compras, produtos, estoque, relatório...) |

- Nomes reservados, que não podem ser endereço de igreja: `login`, `logout`, `painel`,
  `convite`, `perfil`, `admin`, `api`, `assets`, `config`, `database`, `docs`, `termos`.
- Endereços só com letras minúsculas, números e hífen.
- Precisa de um `.htaccess` na raiz para o Apache encaminhar esses endereços ao sistema.
  A hospedagem atual (cPanel) suporta.
- Os dados de hoje viram a **primeira igreja e o primeiro encontro**, com um mercado.

---

## 7. Telas novas

### 7.1 Página de apresentação (`/`)
O que é o Carmelito's, para quem serve, o que ele faz (fiado por pessoa e equipe, estoque,
pagamentos com juros, relatório) e o botão "Entrar". Sem cadastro aberto: clientes entram
por convite. Link para os termos de uso e privacidade.

### 7.2 Painel geral do dono (`/painel`)
- Igrejas: nome, endereço, plano, validade, quantos encontros.
- Dentro de cada igreja: os encontros, o mercado atual, a situação e o total vendido.
- Criar igreja e encontro; gerar convites.
- Definir plano e validade; marcar pagamento recebido.
- **Entrar** em qualquer painel de paróquia ou encontro. Uma faixa fixa no topo avisa
  onde está e tem o botão "Voltar ao meu painel".
- Mercados arquivados: ver e recuperar.

### 7.3 Painel da paróquia
- Todos os encontros dela, com o mercado atual, a situação e os totais de cada um.
- Criar encontro e mercado; convidar admins.
- Entrar em cada encontro (mesma faixa de aviso).
- Encerrar, reabrir e arquivar mercados.
- Identidade da paróquia: nome, logo, cores.

### 7.4 Admin do encontro
O dashboard e as telas de hoje, mais:

- **Equipe de venda**: convidar (admin ou vendedor), ver vínculos, remover.
- **Personalização**: nome, logo, cores (ou usar as da paróquia).
- **Mercados**: encerrar o atual, criar o próximo (com a opção de copiar produtos),
  ver os relatórios dos anteriores.

### 7.5 Vendedor
- Abre direto na **venda** (a tela inicial de hoje), simples e feita para celular.
- Menu curto: **Vender · Minhas vendas · Quem deve / receber · Total do mercado**.
- Pode lançar compra já paga, escolhendo a forma de pagamento (com juros).
- Não aparece nada de cadastro, estoque, juros, desconto, cortesia ou exclusão.

### 7.6 Perfil
Nome, trocar WhatsApp, trocar senha, e a lista dos mercados em que a pessoa está.

### 7.7 Termos de uso e privacidade
Página simples, publicada antes de abrir para clientes de fora: a plataforma guarda nomes
e dívidas de pessoas de várias organizações.

---

## 8. Banco de dados

**Um banco só para todos.** O isolamento é feito por uma coluna que diz a qual **mercado**
cada registro pertence.

### Tabelas novas

| Tabela | Guarda |
|---|---|
| `accounts` | Contas: nome, WhatsApp (único), senha, se é o dono da plataforma, ativa |
| `churches` | Igrejas: nome, `slug` (único), cidade, logo, cores, plano (paróquia ou nenhum), tipo de cobrança (anual/mensal), validade |
| `gatherings` | Encontros: igreja, nome, `slug` (único na igreja), logo e cores próprios (opcionais) |
| `markets` | Mercados: encontro, nome (ex.: "2026"), datas, situação (aberto/encerrado/arquivado), pago (avulso), quem encerrou e quando |
| `memberships` | Vínculos: conta, papel, e a igreja, o encontro ou o mercado; situação |
| `invites` | Convites e redefinições de senha: código, tipo, papel, alvo, validade, usado por |
| `audit_log` | Ações importantes: quem, o quê, onde, quando |

A tabela `admins` de hoje é migrada para `accounts` + `memberships`.

### Tabelas que ganham `market_id`

`categories`, `products`, `teams`, `people`, `purchases`, `purchase_items`,
`purchase_payments`, `stock_movements`, `payment_fees`.

- Unicidades passam a valer **dentro do mercado** (ex.: nome de categoria).
- `payment_fees` passa a ter uma linha por forma de pagamento **por mercado**.

### Tabelas que ganham "quem fez" (`created_by` → `accounts.id`)

`purchases`, `purchase_payments`, `stock_movements`, e os registros de desconto/cortesia.

### Migração dos dados atuais
Criar uma igreja, um encontro e um mercado para o que existe hoje, e marcar todos os
registros com esse mercado. Registros antigos ficam sem "quem fez".
A tabela antiga `payments`, sem uso, pode ser removida nessa migração.

---

## 9. O ponto que exige cuidado

Hoje **cada tela faz suas próprias consultas**, sem noção de igreja, encontro ou mercado.
São cerca de 35 arquivos PHP em `admin/` e `api/`, cada um com a sua checagem de login.

Com vários clientes, **toda** consulta precisa filtrar pelo mercado certo. Uma consulta
esquecida faz um encontro ver ou alterar dados de outro. Esse é o maior risco do projeto.

Como reduzir o risco:

1. **Um ponto único de contexto** (`config/context.php`): a partir do endereço e da sessão,
   descobre a conta, a igreja, o encontro, o mercado e o papel — e barra quem não tem
   vínculo. Toda página começa por ele, no lugar do `if (!isset($_SESSION['admin_id']))`
   repetido.
2. **Funções de permissão** (`can('sell')`, `require_role(...)`), chamadas em todo lugar
   que grava. Esconder botão não protege nada sozinho.
3. **IDs vindos do navegador sempre conferidos com o mercado**: abrir a compra nº 50 de
   outro encontro tem que dar "não encontrado".
4. **Roteiro de isolamento**: dois encontros de teste em igrejas diferentes, e uma
   sequência que tenta, logado em um, ler e alterar cada tipo de registro do outro.
5. **Mercado encerrado, arquivado ou com plano vencido bloqueia no servidor.**

---

## 10. Ordem de construção

O sistema continua funcionando ao fim de cada etapa.

| Etapa | Entrega | Como saber que terminou |
|---|---|---|
| **1. Contas e papéis** | `accounts`, login por WhatsApp, papéis admin e vendedor no mercado atual, bloqueio no servidor, tela do vendedor, "vendido por" | um vendedor entra, vende e recebe, e não consegue abrir nem gravar nada de admin |
| **2. Igrejas, encontros e mercados** | as tabelas novas, `market_id` em tudo, endereços `/igreja/encontro`, contexto único, dados atuais migrados | dois encontros de teste não enxergam nada um do outro (roteiro de isolamento) |
| **3. Ciclo do mercado** | encerrar, reabrir, arquivar, recuperar; mercado novo com cópia de produtos; relatórios dos anteriores; congelamento dos vendedores | mercado encerrado recusa venda; admin ainda recebe; vendedor perde o acesso |
| **4. Painel geral do dono** | igrejas, encontros, convites, entrar nos painéis, plano e validade | cliente novo é criado e convidado sem tocar no banco |
| **5. Painel da paróquia** | visão de todos os encontros, criar encontro, convidar admins, entrar em cada um | uma paróquia com dois encontros administra os dois de um lugar só |
| **6. Equipe e convites** | convites por link com escolha de papel, redefinição de senha, perfil, conta com vários vínculos | o mesmo vendedor trabalha em dois mercados com uma conta só |
| **7. Planos** | paróquia × avulso, validade, modo consulta quando vence, mover avulso para a paróquia | plano vencido deixa o encontro só para consulta |
| **8. Personalização** | nome, logo e cores por igreja e por encontro | dois encontros com caras diferentes |
| **9. Apresentação e termos** | página em `/` com login, termos de uso e privacidade | visitante entende o produto e consegue entrar |

A Etapa 1 pode ir ao ar sozinha e já entrega o "vendido por Fulano" no mercado atual.
A Etapa 2 é a mais delicada: mexe em todas as tabelas e em todas as telas.

---

## 11. Infraestrutura

- A hospedagem atual (Turbo Cloud, cPanel) atende no começo.
- Mudanças de banco continuam sendo scripts em `database/migrations/`, rodados no
  phpMyAdmin **antes** do deploy. A migração da Etapa 2 altera todas as tabelas:
  **backup completo do banco antes** e ensaio numa cópia.
- Logos enviadas pelos clientes precisam de uma pasta fora do git, que o deploy não apague,
  com validação do tipo e do tamanho da imagem.
- Com vários clientes dependendo do sistema durante um encontro, vale um **backup
  automático diário** (hoje é manual).

---

## 12. Ainda em aberto

1. **Modelo de negócio.** O dono vai explicar como quer que funcione a venda da
   plataforma: preços da paróquia (anual e mensal) e do mercado avulso, e eventuais
   limites por plano. Até lá: sem limites, e plano e validade marcados à mão.
2. **Como a paróquia e o encontro avulso chegam até a plataforma** (hoje: só por convite
   do dono).

### Raciocínio para chegar nos preços (ponto de partida, não tabela)

Os valores não travam a construção: o sistema só guarda o plano e a validade.

- **Começar pelo avulso.** O cliente compara com o que o mercado dele fatura no encontro.
  Faixa para testar nas conversas: 1% a 3% do faturamento típico de um mercado,
  cobrado como **valor fixo**.
- **Paróquia a partir do avulso.** O anual precisa compensar para quem faz dois ou três
  encontros por ano: algo como o preço de dois avulsos, com encontros ilimitados.
  O mensal é o anual dividido por 12 com um acréscimo, para o anual parecer vantagem.
- **Testar antes de fixar.** Os primeiros clientes (o próprio encontro do dono e um ou
  dois conhecidos) entram de graça ou com desconto, em troca de uso real e da resposta
  "quanto você pagaria?".
- **Evitar no começo:** cobrar porcentagem das vendas (obriga a auditar o cliente),
  muitos planos (bastam paróquia e avulso) e limites por plano.
- **Custo a considerar:** hospedagem e domínio são baixos; o custo real é o tempo de
  suporte nos fins de semana de encontro, maior nas paróquias.

As faixas acima não vêm de pesquisa de mercado; servem só para abrir a conversa com os
primeiros clientes.
