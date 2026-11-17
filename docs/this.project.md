# this.project — Finalização do Microblog: área pública + busca

## Origem da demanda

Atividade avaliativa (entrega por branch + arquivo Markdown de prompts). Enunciado:

1. **`index.php`** — exibir as notícias em destaque (imagem, título e resumo) com
   link dinâmico para `noticia.php?id=...`; e exibir todas as notícias, com a
   programação feita em `includes/todas.php`.
2. **`noticia.php`** — exibir a notícia e seus detalhes: data (dia/mês/ano),
   autor, imagem e texto. Sem autor associado → exibir "Equipe Microblog".
3. **`noticias-por-categoria.php`** — exibir as notícias filtradas pela categoria
   escolhida no menu do site, com título, data (dia/mês/ano), autor e resumo;
   sem autor → "Equipe Microblog"; link dinâmico para `noticia.php`.
4. **Atenção do enunciado:** primeiro programar `includes/cabecalho.php` para o
   menu mostrar todas as categorias existentes.
5. **🔥 Desafio (busca):** programar `resultados.php` (PHP, comunicação com as
   classes de banco) e `js/busca.js` (fetch + interações de front-end), com o
   mesmo comportamento do site original.

Referência de funcionamento: <https://microblog-tec.tiagohub.com/>

Entrega: link da branch com a atividade e um Markdown organizado com o histórico
de prompts usados ao longo da atividade.

## Estado do projeto antes da intervenção

- Projeto didático em PHP com classes OO (`src/`), MySQL/MariaDB via PDO e
  Bootstrap. Idioma e nomes em português. Regras completas em `AGENTS.md`.
- Área administrativa (usuários, categorias, notícias) já funcional.
- `NoticiaServico` possuía apenas `listarTodos(TipoUsuario, int)` (com filtro de
  perfil), `buscarPorId(int, TipoUsuario, int)` e `inserir(Noticia)`.
- `CategoriaServico::listarTodos()` e `buscarPorId()` já existiam.
- Todas as páginas públicas eram conteúdo estático provisório; o menu de
  categorias tinha itens fixos; `js/busca.js` estava vazio (`'use strict';`).
- Helpers existentes e reutilizáveis: `Utils::escaparHtml`, `Utils::verificarId`
  (redireciona para `index.php` em id inválido), `Utils::normalizarEntrada`,
  `Utils::formataData` (`d/m/Y H:i`), `Utils::registrarLog`, `Utils::alertaErro`
  (`<script>alert(...); history.back();</script>`).
- O banco local estava com schema antigo (incompatível com o código atual) e o
  dump canônico da aula é `_materiais/microblog_2026_1611.sql` (11 notícias,
  6 destaques, 7 categorias, 4 usuários - ids 23 a 33).

## Decisões alinhadas com o desenvolvedor

| Tema | Decisão |
|---|---|
| Banco local | Importar o dump 1611 em banco novo `microblog_2026` e apontar `ConexaoBD` (arquivo é *skip-worktree*: não versiona credenciais) |
| Divisão de trabalho | Implementação assistida pelo agente, bloco a bloco, com o histórico de prompts registrado em `prompts.md` |
| Formato de data exibida | `d/m/Y H:i` via `Utils::formataData()` (igual ao site original e ao admin), com ISO no atributo `datetime` do `<time>` |
| Branch | Nova branch `10_aula-1711` a partir de `09_aula-1611`, com commits atômicos por bloco |

## Contrato extraído do site de referência (evidência por HTTP)

- **Menu:** todas as categorias, ordenadas por nome (`ORDER BY nome`).
- **Destaques:** um card por notícia com `destaque = 'sim'`, sem `LIMIT`
  (o original exibe 6). Card = `article.card` > `a.card-link` > `img.card-img-top`
  + `div.card-body` (`h3.fs-4.card-title` + `p.card-text`).
- **Todas as notícias:** `ORDER BY data DESC`; item de `list-group` com
  `<time>` + título + resumo, sem autor; `<h2 class="fs-6 text-center text-muted">`.
- **Detalhe:** `h2` com título; parágrafo `font-weight-light` com `<time>` e
  `<span>` do autor; `img.float-start.pe-2.img-fluid`; texto em `.ajusta-texto`
  (`white-space: pre-wrap`). `id` inválido → 302; `id` inexistente →
  `alert('Notícia não encontrada.')`.
- **Categoria:** `<h2 class="text-center">Notícias sobre <span class="badge bg-primary">NOME</span></h2>`
  e itens com título, data, autor ("Equipe Microblog" quando nulo) e resumo. Sem
  notícias (ou categoria inexistente) → `<h2 class="alert alert-warning text-center">Não há notícias desta categoria</h2>`
  mantendo o bloco de lista (vazio) renderizado.
- **Busca:** `POST` do campo `busca`; termo sofre `trim`; `LIKE %termo%`
  case-insensitive em **título + resumo + texto** (não pesquisa autor);
  `%`/`_` não são escapados (`%` devolve todas); `ORDER BY data DESC`; zero
  resultados, termo vazio ou `GET` direto → `<h2 class="fs-5 text-danger">Sem notícias</h2>`;
  com resultados → `<h2 class="fs-5">Resultados: <span class="badge rounded-pill text-bg-success">N</span></h2>`
  + `list-group` com links para `noticia.php?id=...`.
- **`js/busca.js`:** evento `input`, debounce de 300 ms, `FormData` + `fetch`
  POST em `resultados.php`, descarte de resposta obsoleta, `aria-busy`, e erro
  de rede → `p.alert.alert-warning` "Não foi possível realizar a busca agora."

## Restrições e invariantes

- Não alterar CSS (layout já pronto) nem a casca HTML entregue nos stubs.
- Banco de dados como fonte única de dados (sem hardcode de categorias/listas).
- Prepared statements para todo valor de usuário; escape de saída com
  `Utils::escaparHtml`; erros internos nunca expostos (log + mensagem genérica).
- Nomes, estrutura de erros e padrões idênticos aos já existentes no projeto.
- `src/Database/ConexaoBD.php` é *skip-worktree*: ajuste local de banco não entra
  em commit.
