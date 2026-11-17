# Histórico de prompts — Finalização da área pública do Microblog

Atividade: programar o funcionamento das páginas públicas (`index.php`,
`noticia.php`, `noticias-por-categoria.php`, `includes/todas.php` e o desafio
`resultados.php` + `js/busca.js`).

Branch: `10_aula-1711` (base `09_aula-1611`) — 17/11/2026

Método: cada prompt abaixo foi enviado à IA dentro do VSCode (Copilot/Codex),
com o `AGENTS.md` do projeto no contexto. Todo resultado foi revisado antes de
ser aceito, validado com `php -l`/`node --check` e conferido contra o site de
referência (<https://microblog-tec.tiagohub.com/>), que serve de contrato de
comportamento (markup, mensagens, ordenação e casos de erro).

---

## Prompt 0 — reconhecimento do projeto (contexto)

**Objetivo:** fazer a IA ler as regras do projeto antes de escrever qualquer código.

**Prompt:**

```text
Leia o arquivo AGENTS.md deste projeto e analise o estado atual do código.

Depois, analise as classes da pasta src (em especial NoticiaServico,
CategoriaServico, o modelo Noticia e a classe Utils) e as páginas públicas
index.php, noticia.php, noticias-por-categoria.php, includes/cabecalho.php,
includes/todas.php, resultados.php e js/busca.js.

Liste os padrões que devo seguir: nomes de métodos e variáveis, tratamento de
exceções, escape de saída, prepared statements e verificação de id recebido
por URL. Não altere nenhum arquivo ainda; aguarde o próximo comando.
```

**Resultado:** mapeamento do estado atual. As páginas públicas e o `js/busca.js`
eram apenas conteúdo provisório estático, o menu de categorias estava com itens
fixos e a `NoticiaServico` só tinha métodos da área administrativa
(`listarTodos`, `buscarPorId` com filtro de perfil e `inserir`).

**Verificação:** leitura dos próprios arquivos e confirmação dos padrões
(`Utils::escaparHtml`, `Utils::verificarId`, `Utils::registrarLog`,
`try/catch` de `PDOException` lançando `Exception` genérica).

---

## Prompt 1 — ambiente: banco de dados local

**Objetivo:** conseguir rodar as páginas antes de programá-las.

**Prompt:**

```text
As páginas não abrem porque o banco local está com o schema antigo. Quero
usar o dump mais recente do projeto.

1) Importe o arquivo _materiais/microblog_2026_1611.sql em um banco novo
   chamado microblog_2026 (não sobrescreva o banco microblog que eu já tenho).
2) Ajuste a classe ConexaoBD para apontar para esse banco.
3) Suba um servidor local para eu testar as páginas.
```

**Resultado:** banco `microblog_2026` criado com o dump da aula (11 notícias,
6 destaques, 7 categorias e 4 usuários) e a conexão apontada para ele.

**Verificação:** contagem de registros por tabela via SQL e consulta de teste
com a `NoticiaServico` (`listarTodos`) retornando as 11 notícias.

**Observação:** `src/Database/ConexaoBD.php` está marcado como *skip-worktree*
neste repositório (as credenciais/banco são de cada ambiente), portanto esse
ajuste **não** entra em commit. Cada máquina precisa revisar os valores antes
de rodar o projeto.

---

## Prompt 2 — `includes/cabecalho.php`: menu com todas as categorias

**Objetivo (enunciado):** "primeiro, programe o arquivo `includes/cabecalho.php`
para fazer com que o menu mostre todas as categorias existentes".

**Prompt:**

```text
No arquivo includes/cabecalho.php, substitua os itens fixos do menu de
Categorias por um foreach que liste todas as categorias do banco de dados.

Regras:
- use a classe CategoriaServico (método listarTodos) e não escreva SQL na página;
- como o cabeçalho passará a usar classes do projeto, carregue o autoload
  (vendor/autoload.php) no início do arquivo com require_once;
- escape o nome e o id da categoria com Utils::escaparHtml();
- monte o link no formato noticias-por-categoria.php?id=NUMERO;
- se a conexão com o banco falhar, registre o erro com Utils::registrarLog()
  e deixe o menu vazio: o site não pode parar de funcionar por causa do menu;
- mantenha a estrutura e as classes CSS (Bootstrap) já existentes no arquivo.
```

**Resultado:** menu alimentado pelo banco em `foreach`, com o autoload no topo
do include e `try/catch` protegendo a falha de conexão.

**Verificação:** o HTML gerado passou a listar as 7 categorias na mesma ordem do
site original (Desenvolvimento, Educação, Games, IA, Mercado, Mobile, Segurança);
uma categoria inserida no banco apareceu no menu sem nenhuma alteração de código.

---

## Prompt 3 — `NoticiaServico`: consultas da área pública

**Objetivo:** dar à área pública métodos próprios de leitura, sem reaproveitar
os métodos administrativos (que exigem perfil/sessão).

**Prompt:**

```text
Na classe NoticiaServico, crie os métodos de leitura usados pela área pública,
seguindo exatamente o padrão dos métodos que já existem (try/catch de
PDOException, Utils::registrarLog e Exception com mensagem genérica):

1) listarDestaques(): notícias com destaque 'sim', ordenadas por data
   decrescente, com id, titulo, resumo e imagem;
2) listarTodas(): todas as notícias, ordenadas por data decrescente, com
   id, titulo, data e resumo;
3) listarPorCategoria(int $categoriaId): notícias da categoria escolhida,
   com id, titulo, data, resumo e o nome do autor (LEFT JOIN em usuarios,
   porque a notícia pode não ter autor);
4) buscarPorIdPublico(int $id): os dados completos de uma notícia, incluindo
   o autor, sem o filtro de perfil usado no buscarPorId administrativo;
5) buscarPorTermo(string $termo): id e titulo das notícias em que o termo
   apareça no titulo, no resumo ou no texto, ordenadas por data decrescente,
   usando LIKE com parâmetro nomeado.

Use o enum Destaque (Destaque::SIM->value) no lugar de string fixa e não
duplique a cláusula SQL do autor em cada método além do necessário.
```

**Resultado:** os cinco métodos foram adicionados com prepared statements e
tratamento de erro; `buscarPorId` (administrativo) permaneceu intocado.

**Verificação:** `php -l`, consulta de teste de cada método pelo terminal e
comparação com o site original (a busca do site considera título, resumo e
texto e retorna em ordem de data decrescente).

---

## Prompt 4 — `index.php`: notícias em destaque

**Objetivo (enunciado):** exibir as notícias em destaque com imagem, título e
resumo, com link dinâmico para `noticia.php`.

**Prompt:**

```text
Em index.php, substitua os dois cards provisórios por um foreach que exibe as
notícias em destaque vindas do banco (NoticiaServico::listarDestaques).

Regras:
- cada card deve manter a estrutura atual: article.card, link (a.card-link)
  para noticia.php?id=ID, imagem (images/NOME_DA_IMAGEM), h2.card-title com o
  título e p.card-text com o resumo;
- escape todos os valores com Utils::escaparHtml();
- carregue o autoload no início da página;
- em caso de erro na consulta, registre no log e mostre uma mensagem de alerta
  para o usuário, sem quebrar o layout;
- não altere o CSS nem o includes/todas.php nesta etapa.
```

**Resultado:** `index.php` passou a renderizar um card por destaque, com o link
dinâmico montado a partir do id real da notícia.

**Verificação:** 6 cards renderizados (um por destaque do banco, a mesma
quantidade do site original) e comparação estrutural do card com o site de
referência.

---

## Prompt 5 — `includes/todas.php`: todas as notícias

**Objetivo (enunciado):** exibir todas as notícias "com a programação sendo
feita no arquivo `includes/todas.php`".

**Prompt:**

```text
Programe o arquivo includes/todas.php para listar todas as notícias do banco
usando NoticiaServico::listarTodas.

Regras:
- mantenha o bloco atual (hr, título "Todas as notícias" e a list-group);
- cada item é um link (list-group-item list-group-item-action) para
  noticia.php?id=ID contendo: data formatada, título e resumo;
- formate a data no padrão dia/mês/ano com hora usando Utils::formataData e
  coloque o formato ISO no atributo datetime do elemento <time>;
- ordene por data decrescente (isso é responsabilidade da consulta);
- escape tudo na saída e trate erro de consulta sem quebrar a página;
- esse arquivo é incluído por várias páginas públicas: não imprima
  <html>, <body> nem rodapé dentro dele.
```

**Resultado:** listagem dinâmica em `foreach`, com `<time>` semântico
(`datetime` ISO + texto `d/m/Y H:i`) e alerta de erro isolado.

**Verificação:** 11 itens (todas as notícias do banco) na ordem de data
decrescente; a assinatura estrutural do item ficou idêntica à do site original.

---

## Prompt 6 — `noticia.php`: detalhe da notícia

**Objetivo (enunciado):** exibir notícia, data (dia/mês/ano), autor, imagem e
texto; autor ausente deve virar "Equipe Microblog".

**Prompt:**

```text
Em noticia.php, carregue a notícia pelo id recebido na URL e exiba os dados:

- valide o id antes de consultar, usando Utils::verificarId();
- busque com NoticiaServico::buscarPorIdPublico();
- se o id for válido mas não existir notícia, avise o usuário e volte para a
  página anterior (Utils::alertaErro);
- mostre o título (h2), a data formatada (com o formato ISO no atributo
  datetime), o autor, a imagem (images/NOME) e o texto completo dentro do
  parágrafo com a classe que preserva quebras de linha;
- quando a notícia não tiver autor associado, exiba "Equipe Microblog";
- concentre essa regra do autor em Utils (método nomeAutor) para não repetir a
  mesma condição em mais de uma página;
- escape tudo na saída e trate erro de banco com log.
```

**Resultado:** página dinâmica com validação de id, tratamento de notícia
inexistente e fallback de autor centralizado em `Utils::nomeAutor()`.

**Verificação:** `id=23` exibiu título, data, autor, imagem e texto do banco;
`id` inválido redirecionou para `index.php` (HTTP 302) e `id` inexistente
devolveu o alerta "Notícia não encontrada." — os dois comportamentos iguais aos
do site original. O fallback foi testado com uma notícia temporária sem autor
(`usuario_id` nulo): exibiu "Equipe Microblog".

---

## Prompt 7 — `noticias-por-categoria.php`: notícias da categoria do menu

**Objetivo (enunciado):** exibir as notícias da categoria escolhida no menu,
com título, data, autor e resumo, com link dinâmico para a notícia, e usar
"Equipe Microblog" quando não houver autor.

**Prompt:**

```text
Programe noticias-por-categoria.php usando o id enviado pelo menu:

- valide o id com Utils::verificarId();
- busque o nome da categoria com CategoriaServico::buscarPorId e as notícias
  com NoticiaServico::listarPorCategoria (prepared statement);
- quando houver notícias: mostre o título com o nome da categoria em um badge
  e a lista, onde cada item é um link para noticia.php?id=ID com título, data
  (dia/mês/ano), autor e resumo;
- quando não houver notícias (ou a categoria não existir): mostre a mensagem
  "Não há notícias desta categoria" no lugar do título, mantendo o restante da
  estrutura;
- use Utils::nomeAutor para o fallback do autor;
- escape tudo na saída e trate o erro de banco com log.
```

**Resultado:** página dinâmica, com o bloco de vazio idêntico ao do site
original e o nome da categoria vindo do banco (sem texto fixo).

**Verificação:** categoria 7 (Desenvolvimento) listou 3 notícias; categoria sem
notícias e categoria inexistente exibiram "Não há notícias desta categoria";
notícia sem autor exibiu "Equipe Microblog".

---

## Prompt 8 — DESAFIO: `resultados.php` (PHP da busca)

**Objetivo:** responder à busca do cabeçalho com o fragmento HTML dos
resultados, mantendo a comunicação com o banco dentro do PHP.

**Prompt:**

```text
Programe resultados.php como o endpoint da busca do cabeçalho. Ele não é uma
página completa: devolve apenas um fragmento HTML que o JS injeta na div de
resultados.

Regras:
- receba o termo por POST (campo "busca") e normalize com
  Utils::normalizarEntrada();
- termine o termo em 100 caracteres, o mesmo limite do campo do formulário;
- se o termo estiver vazio ou não houver resultados, devolva
  <h2 class="fs-5 text-danger">Sem notícias</h2>;
- havendo resultados, devolva
  <h2 class="fs-5">Resultados: <span class="badge rounded-pill text-bg-success">N</span></h2>
  seguido de uma list-group com um link para noticia.php?id=ID e o título;
- a busca deve consultar título, resumo e texto (LIKE com parâmetro nomeado),
  em ordem de data decrescente;
- nunca imprima o termo digitado no HTML e escape os títulos exibidos;
- se a consulta falhar, registre no log e devolva um alerta de erro.
```

**Resultado:** endpoint em fragmento, com `prepared statement`, limpeza do
termo e os mesmos textos/markup do site original.

**Verificação:** `android` → 2 resultados; `Stagefright` (presente só no texto)
→ 1; `zzzzz`, POST vazio e acesso direto por GET → "Sem notícias";
`%` → todas as notícias (o site original também não escapa curingas);
espaços nas pontas e maiúsculas são ignorados (` ANDROID ` → 2).

---

## Prompt 9 — DESAFIO: `js/busca.js` (JavaScript da busca)

**Objetivo:** fazer o front-end conversar com `resultados.php` via `fetch`,
com a mesma experiência do site original.

**Prompt:**

```text
Programe o arquivo js/busca.js para atualizar os resultados enquanto o usuário
digita, seguindo o formulário e a div de resultados que já existem no
includes/cabecalho.php (#form-busca, #campo-busca e #resultados).

Regras:
- escute o evento "input" do campo e aguarde 300 ms sem digitação antes de
  consultar (debounce), cancelando o temporizador a cada tecla;
- envie o formulário por fetch (POST) usando FormData, sem recarregar a página;
- enquanto houver consulta em andamento, mantenha aria-busy="true" na div de
  resultados e volte para "false" ao terminar;
- se o usuário apagar o campo (ou digitar só espaços), esconda a área de
  resultados e limpe o conteúdo;
- numere cada consulta e descarte a resposta de uma consulta antiga que chegue
  depois de uma mais recente (evita resultado fora de ordem);
- trate erro de rede exibindo "Não foi possível realizar a busca agora." em uma
  mensagem de alerta dentro da própria div;
- escreva comentários objetivos em português e não use bibliotecas externas.
```

**Resultado:** `js/busca.js` completo (debounce, `fetch` + `FormData`,
`aria-busy`, descarte de resposta obsoleta e fallback de erro).

**Verificação:** teste no navegador digitando no campo — resultados apareceram
após a pausa, clicar em um resultado abriu a notícia correta, apagar o campo
escondeu a área e não houve erro no console.

---

## Prompt 10 — conferência final

**Objetivo:** revisar os casos de borda antes de fechar a branch.

**Prompt:**

```text
Revise os arquivos que alteramos nesta atividade e confirme, sem alterar o CSS:

1) id inválido ou ausente em noticia.php e noticias-por-categoria.php;
2) id válido sem registro (notícia inexistente) e categoria sem notícias;
3) notícia sem autor associado nas duas páginas que exibem autor;
4) busca com termo vazio, com termo inexistente e com caracteres especiais;
5) saída de todos os valores dinâmicos passando por escape;
6) mensagens de erro registradas no log sem expor detalhes do banco;
7) sintaxe de todos os arquivos PHP alterados (php -l) e do JS (node --check).
```

**Resultado:** nenhum ajuste de correção necessário; a conferência gerou as
evidências registradas no resumo da branch.

**Verificação:** `php -l` em todos os arquivos PHP alterados, `node --check` no
`js/busca.js`, comparação dos casos de borda com o site de referência e
navegação real (sem erros de console).

---

## Prompt 11 — auditoria do plano no Plan Council

**Objetivo:** submeter o plano ao conselho multi-modelo antes de encerrar a
entrega, para caçar furos que o autor não vê.

**Prompt:**

```text
Antes de fechar esta entrega, prepare o plano de implementação deste trabalho
(arquivos, contratos de dados, fases, matriz de verificação e riscos) e
submeta-o ao Plan Council, junto com o contexto do projeto.

Ao receber o parecer, faça a triagem: para cada ressalva, confirme no código se
ela procede, aplique o que for ajuste real e registre no plano o que foi aceito
e o que foi mantido de propósito (com a justificativa).
```

**Resultado:** veredito APROVADO por consenso (3/3). Uma ressalva procedente foi
aplicada (desempate `id DESC` na ordenação das consultas públicas); as demais ou
já estavam implementadas, ou foram mantidas por fidelidade ao site de
referência. A suíte de testes formal foi dispensada nesta rodada por decisão do
desenvolvedor.

**Verificação:** execução do script do conselho (saída registrada em
`docs/council_report.md`, com o diálogo entre modelos anexado ao final de
`implementation_plan.md`) e conferência da ressalva aplicada por consulta
repetida (ordem estável) e por `php -l`.

---

## Histórico anterior (branch `09_aula-1611`)

### 1. Pedir pra IA analisar o projeto no estado atual

Analise todas as classes utilizadas até o momento (contidas na pasta src), em especial
a classe NoticiaServico e a classe Noticia.

Veja também as páginas administrativas, em especial usuários.php e categorias.php.

Identifique padrões, como nomes de métodos, propriedades, variáveis, arquivos, namespaces.

Aguarde o próximo comando.

### 2. Criação do método listarTodos

Crie na classe NoticiaServico o método listarTodos, de maneira semelhante ao que foi feito em CategoriaServico e UsuarioServico.

Atenção: a consulta SQL deve no caso de usuários admin, carregar todas as noticias incluindo o nome do autor delas. Se for um usuário editor, a consulta deve carregar apenas as noticias dele próprio, sem incluir o nome do autor.

Tarefas:

- visualizar noticias e nome do autor delas (admin)
- visualizar notícias próprias (editor)
