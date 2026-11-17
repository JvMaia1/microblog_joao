### 2026-11-17 — Finalização da área pública do Microblog e busca assíncrona

Pacote de alterações desta entrega (branch `10_aula_1711`, base `09_aula_1611`):
tornar dinâmicas as páginas públicas, alimentar o menu de categorias pelo banco
e implementar o desafio do sistema de busca (PHP + JavaScript).

**Contexto:** antes da intervenção, `index.php`, `noticia.php`,
`noticias-por-categoria.php`, `includes/todas.php` e `resultados.php` eram
conteúdo provisório estático, o menu de categorias tinha dois itens fixos e
`js/busca.js` estava vazio. Os dados passaram a vir do banco por meio de classes
de serviço, mantendo o layout (Bootstrap) e o comportamento do site de
referência <https://microblog-tiagohub.com/>.

**Arquivos Impactados:**

#### `src/Services/NoticiaServico.php`
- **Linhas Alteradas**: linha 6 (import do enum `Destaque`) e linhas 85-186 (bloco novo de métodos públicos)
- **O que mudou**: adicionados `listarDestaques()`, `listarTodas()`,
  `listarPorCategoria(int)`, `buscarPorIdPublico(int)` e `buscarPorTermo(string)`,
  todos com `prepared statements`, `LEFT JOIN usuarios` para o autor e
  `ORDER BY data DESC, id DESC`.
- **Por que mudou**: a área pública precisa ler notícias sem sessão e sem filtro
  de perfil; os métodos administrativos existentes exigem `TipoUsuario` e
  restringem o editor às próprias notícias, o que não serve ao visitante.
- **Riscos Mitigados**: valores sempre parametrizados (SQL injection);
  `PDOException` registrada em log e convertida em mensagem genérica (nenhum
  detalhe do banco chega à tela); desempate `id` eliminou ordenação instável
  quando duas notícias compartilham o mesmo `data` (apontado pelo Plan Council).

#### `src/Helpers/Utils.php`
- **Linhas Alteradas**: linhas 91-101
- **O que mudou**: `formataDataIso(string)` (data/hora no formato exigido pelo
  atributo `datetime` do elemento `<time>`) e `nomeAutor(?string)` (devolve
  "Equipe Microblog" quando não há autor associado).
- **Por que mudou**: o enunciado exige o fallback do autor e a data semântica em
  duas páginas diferentes; centralizar evita repetir a mesma condição.
- **Riscos Mitigados**: uma única regra para o texto alternativo de autor e uma
  única formatação ISO, evitando divergência entre páginas.

#### `includes/cabecalho.php`
- **Linhas Alteradas**: linhas 1-19 (topo) e 47-55 (menu)
- **O que mudou**: o include passou a carregar o autoload do Composer, a
  consultar as categorias (`CategoriaServico::listarTodos()`) e a gerar um
  `<li>` por categoria; itens fixos removidos.
- **Por que mudou**: requisito explícito do enunciado ("o menu deve mostrar todas
  as categorias existentes") e princípio de dados vêm do banco, sem hardcode.
- **Riscos Mitigados**: `try/catch` com log mantém a página de pé se o banco
  falhar (o menu simplesmente não mostra itens), porque este include é comum a
  todas as páginas públicas.

#### `index.php`
- **Linhas Alteradas**: linhas 1-16 e 20-46
- **O que mudou**: removidos os dois cards estáticos; agora um `foreach` sobre
  `listarDestaques()` renderiza imagem, título e resumo com link dinâmico para
  `noticia.php?id=N`.
- **Por que mudou**: requisito do enunciado (exibir os destaques com link).
- **Riscos Mitigados**: escape de todos os valores na saída; mensagem amigável
  em caso de falha de consulta.

#### `includes/todas.php`
- **Linhas Alteradas**: linhas 1-21 e 23-38
- **O que mudou**: a listagem fixa virou `foreach` sobre `listarTodas()`, com
  data formatada (`d/m/Y H:i`), `datetime` ISO, título, resumo e link.
- **Por que mudou**: requisito do enunciado (todas as notícias programadas neste
  arquivo), que é incluído por várias páginas.
- **Riscos Mitigados**: o include carrega o próprio autoload (não depende da
  ordem de inclusão da página) e isola seu alerta de erro em variável própria,
  para não colidir com as variáveis das páginas.

#### `noticia.php`
- **Linhas Alteradas**: linhas 1-26 (cabeçalho PHP) e 32-40 (view)
- **O que mudou**: valida o `id` (`Utils::verificarId`), busca a notícia
  (`buscarPorIdPublico`), trata o caso inexistente (`Utils::alertaErro`) e exibe
  título, data, autor, imagem e texto.
- **Por que mudou**: requisito do enunciado (detalhe da notícia, data
  dia/mês/ano e autor com fallback "Equipe Microblog").
- **Riscos Mitigados**: id inválido redireciona para a home (mesmo comportamento
  do site de referência, HTTP 302); texto escapado preserva as quebras de linha
  pela classe `.ajusta-texto` já existente no CSS.

#### `noticias-por-categoria.php`
- **Linhas Alteradas**: linhas 1-27 (cabeçalho PHP) e 33-70 (view)
- **O que mudou**: valida o `id`, busca o nome da categoria e as notícias
  (`CategoriaServico::buscarPorId` + `NoticiaServico::listarPorCategoria`) e
  renderiza o título com badge ou a mensagem "Não há notícias desta categoria".
- **Por que mudou**: requisito do enunciado (filtro pela categoria do menu, com
  título, data, autor e resumo).
- **Riscos Mitigados**: nome da categoria vem do banco (sem texto fixo); o bloco
  de lista é renderizado mesmo no caso vazio, igual ao site de referência.

#### `resultados.php`
- **Linhas Alteradas**: arquivo inteiro (12 linhas provisórias -> 42 linhas)
- **O que mudou**: passou a ser o endpoint real da busca, devolvendo fragmento
  HTML com os estados "resultados", "sem notícias" e "erro".
- **Por que mudou**: desafio do enunciado (PHP responsável pela comunicação com
  o banco).
- **Riscos Mitigados**: termo normalizado e limitado a 100 caracteres (mesmo
  limite do input); `LIKE` parametrizado; o termo **nunca** é impresso no HTML
  (sem XSS refletido); falha de banco logada e respondida com mensagem amigável.

#### `js/busca.js`
- **Linhas Alteradas**: arquivo inteiro (2 linhas -> 78 linhas)
- **O que mudou**: busca viva com debounce de 300 ms, `fetch` POST com
  `FormData`, numeração de consulta para descartar resposta obsoleta,
  `aria-busy` e mensagem de erro na própria área de resultados.
- **Por que mudou**: desafio do enunciado (JavaScript responsável pela
  comunicação com `resultados.php` e pelas interações de front-end).
- **Riscos Mitigados**: `!resposta.ok` impede injetar corpo de erro no DOM
  (apontado pelo Plan Council e já contemplado); campo vazio limpa e esconde a
  área; nenhuma dependência externa foi adicionada.

#### `AGENTS.md`, `prompts.md`, `resumo.md` (preexistentes, documentação)
- **O que mudou**: `AGENTS.md` atualizou o estado funcional (páginas públicas
  dinâmicas, métodos públicos do serviço, novos helpers e a busca);
  `prompts.md` recebeu o histórico organizado dos prompts desta atividade;
  `resumo.md` passou a descrever a branch `10_aula_1711`.
- **Por que mudou**: manter o guia do projeto e o histórico de prompts coerentes
  com o código entregue (o histórico de prompts é item da entrega).
- **Riscos Mitigados**: a distinção entre `listarTodas()` (público) e
  `listarTodos()` (administrativo) ficou documentada para futuras sessões.

#### Arquivos Novos
- `docs/this.project.md` — **Status**: Arquivo Novo. Consolida o enunciado, o
  contrato extraído do site de referência e as decisões alinhadas; serve de
  contexto para auditoria (Plan Council).
- `implementation_plan.md` — **Status**: Arquivo Novo. Plano técnico com
  contratos de dados, fases, matriz de verificação, riscos e o parecer do Plan
  Council (com o mural de diálogo entre modelos).
- `docs/council_report.md` — **Status**: Arquivo Novo. Relatório completo da
  deliberação multi-modelo (vereditos, ressalvas e estimativas).
- `docs/documentacao.md`, `docs/diffs_arquivos_existentes.md`, `docs/fluxo.md` —
  **Status**: Arquivos Novos. Memorial técnico, diffs consolidados e mapa de
  fluxo desta entrega.
