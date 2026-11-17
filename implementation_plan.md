# implementation_plan.md — Finalização da área pública do Microblog + busca

## Ambiente de Execução (Enxuto)

- **PHP:** 8.4.27 local (produção de referência roda 8.3; projeto usa enums, 8.1+)
- **Runtime:** Bun 1.4.2 / Node (apenas ferramentas; nenhuma dependência JS nova)
- **Banco de Dados:** MySQL 8.0.46 local, dump gerado em MariaDB 10.4
  (`_materiais/microblog_2026_1611.sql`, banco `microblog_2026`)
- **SO / Servidor:** Linux, PHP built-in server (`php -S 127.0.0.1:8000`) para
  validação local; o layout usa Bootstrap 5.3 via CDN

> **Estado de execução:** plano já implementado e verificado na branch
> `10_aula-1711` (commits `2520b28`, `ef1b3a7`, `df35006`, `01f27c1`, `389e21a`).
> A submissão ao conselho é uma **auditoria adversarial do plano e do resultado**
> (caça de furos, riscos e regressões), não um pedido de liberação para começar.

## Objetivo

Tornar dinâmicas as quatro páginas públicas, o menu de categorias, o include de
listagem e o sistema de busca do cabeçalho, sem alterar CSS/layout, mantendo os
padrões de arquitetura do projeto (serviços com PDO, models, enums, helpers) e
o comportamento do site de referência.

**Fora de escopo:** área administrativa, atualização/exclusão de notícias,
alterações de schema, alterações de CSS, novas dependências.

## Arquivos

| Ação | Arquivo | Responsabilidade |
|---|---|---|
| [MODIFY] | `src/Services/NoticiaServico.php` | 5 métodos públicos de leitura (destaques, todas, por categoria, por id público, por termo) |
| [MODIFY] | `src/Helpers/Utils.php` | `formataDataIso()` (atributo `datetime`) e `nomeAutor()` (fallback "Equipe Microblog") |
| [MODIFY] | `includes/cabecalho.php` | autoload + menu de categorias do banco, protegido por `try/catch` |
| [MODIFY] | `index.php` | cards de destaque |
| [MODIFY] | `includes/todas.php` | listagem completa (include compartilhado) |
| [MODIFY] | `noticia.php` | detalhe da notícia + validação de id + caso inexistente |
| [MODIFY] | `noticias-por-categoria.php` | listagem por categoria + caso vazio |
| [MODIFY] | `resultados.php` | endpoint da busca (fragmento HTML) |
| [MODIFY] | `js/busca.js` | busca assíncrona (debounce + fetch + estados) |
| [NEW] | `docs/this.project.md`, `prompts.md` (atualizado), `resumo.md` (atualizado) | contexto e artefatos de entrega |

## Contratos de dados (serviço)

- `listarDestaques(): array` → `SELECT id, titulo, resumo, imagem FROM noticias WHERE destaque = :destaque ORDER BY data DESC` (`Destaque::SIM->value`).
- `listarTodas(): array` → `SELECT id, titulo, data, resumo FROM noticias ORDER BY data DESC`.
- `listarPorCategoria(int $categoriaId): array` → `SELECT ... usuarios.nome AS autor FROM noticias LEFT JOIN usuarios ... WHERE categoria_id = :categoria_id ORDER BY data DESC`.
- `buscarPorIdPublico(int $id): ?array` → mesmo `SELECT` de `buscarPorId` (com `LEFT JOIN usuarios`) **sem** o filtro de perfil; `fetch() ?: null`.
- `buscarPorTermo(string $termo): array` → `WHERE titulo LIKE :termo OR resumo LIKE :termo OR texto LIKE :termo ORDER BY data DESC`, bind `%termo%`.
- Todos seguem o padrão existente: `catch (PDOException)` → `Utils::registrarLog($e)` → `throw new Exception("mensagem genérica")`.
- Os métodos administrativos (`listarTodos`, `buscarPorId`, `inserir`) permanecem intactos.

## Fases

1. **F0 — Ambiente:** importar dump 1611 em `microblog_2026`, apontar `ConexaoBD`
   (skip-worktree, sem commit), subir servidor local e validar conexão.
2. **F1 — Serviço:** adicionar os 5 métodos públicos.
3. **F2 — Menu:** `includes/cabecalho.php` com autoload + `CategoriaServico`.
4. **F3 — Home:** `index.php` (destaques) e `includes/todas.php` (todas).
5. **F4 — Detalhe:** `noticia.php` (validação de id, autor com fallback).
6. **F5 — Categoria:** `noticias-por-categoria.php` (nome do banco, caso vazio).
7. **F6 — Busca:** `resultados.php` + `js/busca.js`.
8. **F7 — Verificação:** `php -l`, `node --check`, comparação com o site de
   referência, casos de borda.
9. **F8 — Entrega:** commits atômicos, `prompts.md`, `resumo.md`, `AGENTS.md`.

## Matriz de verificação (conceitual)

| Caso | Expectativa |
|---|---|
| Home | 6 cards de destaque com link `noticia.php?id=N`; 11 itens em "Todas as notícias" com data `d/m/Y H:i` e `datetime` ISO |
| Detalhe | título, data, autor, imagem e texto do banco; `id` ausente/`abc` → 302 para `index.php`; `id` válido inexistente → alerta "Notícia não encontrada." |
| Autor nulo (notícia temporária, `usuario_id = NULL`) | "Equipe Microblog" em `noticia.php` e em `noticias-por-categoria.php` |
| Categoria | nome vindo do banco; categoria sem notícias e categoria inexistente → "Não há notícias desta categoria" |
| Menu | categoria inserida no banco aparece sem alteração de código; falha de banco não derruba a página |
| Busca (`android`, `Stagefright`, `zzzzz`, vazio, `GET`, `%`, ` ANDROID `) | 2, 1, "Sem notícias", "Sem notícias", "Sem notícias", 11, 2 |
| Front-end | digitação → resultados após a pausa; clique abre a notícia; campo vazio esconde a área; sem erros de console |
| Estático | `php -l` em todos os PHP alterados; `node --check` em `js/busca.js` |

## Riscos e mitigações

| Risco | Mitigação |
|---|---|
| Cabeçalho (include comum) com falha de banco derruba todas as páginas | `try/catch` + log; menu vazio |
| Colisão de nomes de método público/administrativo | `listarTodas()` (público) x `listarTodos()` (admin); documentado no `AGENTS.md` |
| `ConexaoBD` apontando para banco sem o schema novo | dump canônico documentado; arquivo skip-worktree (ajuste local) |
| Termo de busca refletido no HTML (XSS) | termo nunca impresso; apenas títulos escapados |
| Respostas fora de ordem no `fetch` | contador de consulta que descarta resposta antiga |
| SQL injection na busca | prepared statement com `LIKE :termo` |
| Divergência do site original | comparação de markup/comportamento por HTTP e navegação real |

## Ajustes pós-conselho (triagem do Maestro)

Veredito: **APROVADO** por consenso (3/3). Recomendações avaliadas contra o código
já implementado:

| Recomendação | Destino |
|---|---|
| `if (!response.ok) throw` em `js/busca.js` | Já implementado desde `01f27c1` (`js/busca.js:47`) |
| `try/catch` + `registrarLog` + fallback amigável nas páginas públicas | Já implementado em `index.php`, `includes/todas.php`, `noticia.php`, `noticias-por-categoria.php`, `includes/cabecalho.php` e `resultados.php` |
| `includes/todas.php` dependeria do autoload da página chamadora | Não procede: o include carrega o autoload por conta própria |
| Guard de termo vazio/GET em `resultados.php` | Já implementado: sem termo válido não há consulta ao banco |
| Ordenação não-determinística em empates de `data` | **Aceita**: `ORDER BY data DESC, id DESC` nos quatro métodos de leitura pública (o site original já apresenta esse desempate em empates reais: ids 26→25 com o mesmo timestamp). Ordem estável em 5 execuções |
| Verificar collation pós-importação e `id` `0`/negativo | **Aceita e verificada**: `utf8mb4_general_ci` consistente no banco importado; `id` `0`, negativo e `abc` redirecionam (302) via `Utils::verificarId` |
| `Utils::alertaErro` usa `history.back()` sem histórico (acesso direto) | **Mantida a fidelidade**: é o comportamento do site de referência e o helper existente do projeto |
| Divergência de versão de PHP (README 8.5+ x ambiente 8.3/8.4) | Registrada em `resumo.md` (documentação apenas) |

Suíte de testes formal (`/tests/`) dispensada por decisão explícita do
desenvolvedor nesta rodada; a verificação usada está listada em `resumo.md`.

## Deliberação do Conselho & Diálogo entre Modelos
<!-- Seção gerenciada pelo plan-council.ts: anexa notas sucintas dos modelos a cada rodada -->
