# Relatório Plan Council

- **Data/Hora**: 2026-11-17T12:15:33.383Z
- **Arquivo Avaliado**: `implementation_plan.md`
- **Modelos Avaliadores**: `deepseek/deepseek-v3.2`, `meta/muse-spark-1.3-contributor`, `xiaomi/mimo-v2.6-pro`
- **Esforço de Raciocínio**: **HIGH**
- **Resultado do Conselho**: **APROVADO** (3/3 válidos)

---

## Síntese do Conselho

| Modelo | Veredito | Tempo | Raciocínio (Tokens) |
|---|---|---|---|
| `deepseek/deepseek-v3.2` | **APROVADO** | 173237ms | 8124 |
| `meta/muse-spark-1.3-contributor` | **APROVADO** | 44745ms | 2270 |
| `xiaomi/mimo-v2.6-pro` | **APROVADO** | 63047ms | 1322 |

---

## Diálogo Entre Modelos (Mural da Rodada)

- **`deepseek/deepseek-v3.2`** (`APROVADO`): Concordo no essencial: plano é mínimo, DB-first e sem overengineering; não deve ser bloqueado por melhorias futuras. - Alerta prioritário: validar `categoria_id`, capturar `Exception` nas páginas/`resultados.php` e confirmar collation/compatibilidade MySQL 8.0/PHP 8.3. - Se outro parecerista pedir `LIMIT`, full-text ou testes automatizados extensos, tratar como evolução, não como bloqueio.
- **`meta/muse-spark-1.3-contributor`** (`APROVADO`): Converjo que plano é fiel ao original e DB-first sem hardcode; único ponto a vigiar pelos pares é guard de termo vazio/GET e `try/catch` nas páginas. Não vejo base para RESSALVAS/REJEITADO.
- **`xiaomi/mimo-v2.6-pro`** (`APROVADO`): Concordo com a arquitetura e o corte de escopo; meu único alerta prioritário é o `response.ok` ausente no `busca.js` — erro HTTP vira HTML injetado no DOM. Discordo de quem exigir rollback/transação: são só operações de leitura, não há risco de perda de dados. Questiono apenas a determinismo da ordenação sem `id` como desempate.

---

## Parecer: `deepseek/deepseek-v3.2`

### VEREDITO: APROVADO

- **APROVADO**: O plano resolve o escopo de ponta a ponta, é DB-first, mantém o padrão do projeto e não introduz complexidade injustificada. Os riscos abaixo são ajustes de robustez/validação, não bloqueadores arquiteturais.

#### 1. Furos de Arquitetura & Premissas Frágeis
- Premissa de fidelidade ao site original depende de comportamento de `LIKE` no MySQL/MariaDB: case-insensitivity, acentuação, `%`/`_` e ordenação por `data DESC` podem variar conforme collation e dados.
- Dump gerado em MariaDB 10.4 sendo importado em MySQL 8.0.46 não é trivialmente compatível: collations, `AUTO_INCREMENT`, `CURRENT_TIMESTAMP` e charset precisam ser validados em banco limpo.
- PHP local 8.4.27 vs produção de referência 8.3 exige confirmação de que não há sintaxe ou comportamento específico de 8.4; o plano não define verificação explícita em 8.3.
- `listarTodas()`, `listarDestaques()` e `buscarPorTermo()` são queries sem `LIMIT`; aceitável para dataset didático de 11 notícias, mas frágil se a base crescer.
- O plano protege explicitamente o cabeçalho contra falha de banco, mas não deixa claro que todas as páginas públicas e `resultados.php` capturam `Exception` vinda dos serviços; sem isso, uma falha de PDO derruba o conteúdo.
- `ConexaoBD` com `skip-worktree` valida o ambiente local, mas não prova compatibilidade com produção; drift de schema/credenciais continua sendo um risco operacional.
- Servidor embutido do PHP pode mascarar concorrência real no teste de `fetch` com debounce; o contador de consulta é a proteção correta, mas a validação local não é equivalente a produção.

#### 2. Omissões Críticas & Casos de Borda
- `noticias-por-categoria.php`: falta explicitar validação robusta para `categoria_id` ausente, `abc`, `0`, negativo, array ou valor não inteiro. O plano trata “categoria inexistente”, mas não trata entrada inválida com o mesmo rigor de `noticia.php`.
- Páginas públicas: falta declarar explicitamente `try/catch` para `Exception` genérica dos serviços em `index.php`, `includes/todas.php`, `noticia.php`, `noticias-por-categoria.php` e `resultados.php`.
- `resultados.php`: se `busca` vier como array, objeto ou valor não escalar, `Utils::normalizarEntrada()`/`trim()` pode falhar ou gerar comportamento inesperado. É preciso garantir entrada escalar/string.
- Imagem nula ou caminho inexistente em destaques pode quebrar o card; o plano não define fallback visual ou omissão do `<img>`.
- Datas nulas/inválidas não são tratadas explicitamente em `Utils::formataData()` e `Utils::formataDataIso()`; se houver registro fora do padrão, a página pode falhar.
- Log em `logs/erros.log` dentro da raiz web pode ficar acessível via HTTP em ambiente mal configurado; não há recomendação de proteção do log.
- Documentação interna tem inconsistência: `AGENTS.md` cita `microblog_2026_completo.sql`/`microblog_2026_inicial.sql`, mas o plano usa `microblog_2026_1611.sql` como dump canônico. Isso pode confundir nova execução.
- Busca com termo muito longo, múltiplos `%`/`_` ou base maior pode causar custo desnecessário; o comportamento é intencional para replicar o original, mas deveria estar documentado como tradeoff.
- Ordenação apenas por `data DESC` pode ficar não determinística em empates; `id DESC` como desempate seria mais estável sem alterar o objetivo.

#### 3. Complexidade Excessiva & Oportunidades YAGNI
- Não adicionar full-text search, paginação, cache, microserviços, event sourcing, testes automatizados extensos ou camada extra de abstração. O escopo é leitura pública e busca simples.
- `LIMIT` opcional em destaques/todas pode ser adiado enquanto a base for didática; não é necessário agora.
- CSRF para busca pública é desnecessário porque não há operação de estado.
- Novas dependências JS/PHP devem permanecer fora do escopo; o plano já respeita isso.
- `docs/this.project.md`, `prompts.md` e `resumo.md` são artefatos de entrega; não expandir para documentação excessiva.

#### 4. Recomendações Cirúrgicas de Ajuste
- Em `noticias-por-categoria.php`, validar `categoria_id` com `Utils::verificarId()` ou equivalente: id inválido → 302 para `index.php`; id válido inexistente → mensagem “Não há notícias desta categoria”.
- Envolver todas as consultas públicas em `try/catch` para `Exception`, registrar log e renderizar estado seguro: lista vazia, alerta genérico ou “Sem notícias” em `resultados.php`.
- Em `resultados.php`, tratar `busca` defensivamente: rejeitar array, converter para string apenas se escalar, aplicar `trim` e, se desejado, limitar tamanho do termo para evitar termos patológicos sem fugir do escopo.
- Manter o comportamento de `%`/`_` como decisão explícita de compatibilidade com o site original, mas documentar que isso é um tradeoff de busca didática, não segurança/performance.
- Validar o dump `microblog_2026_1611.sql` em MySQL 8.0 limpo, registrar collation usada e testar casos `android`, `ANDROID`, ` ANDROID `, `%` e `_`; se a collation divergir, ajustar dump ou documentar a exigência de collation.
- Executar `php -l` ou CI com PHP 8.3 para garantir compatibilidade com produção de referência; evitar qualquer sintaxe 8.4-only.
- Adicionar fallback para `imagem` nula/inexistente nos cards de destaque, por exemplo omitir `<img>` ou usar placeholder, sem alterar CSS.
- Garantir que `Utils::formataData()` e `Utils::formataDataIso()` tolerem `null`/data inválida com valor seguro ou omissão do `<time>`.
- Proteger `logs/` contra acesso HTTP ou mover o log para fora da raiz pública; isso é higiene operacional, não overengineering.
- Alinhar `AGENTS.md`/docs com o dump canônico realmente usado, removendo ambiguidade entre `1611`, `completo` e `inicial`.
- Adicionar desempate `id DESC` nas listagens se houver necessidade de estabilidade visual, mas sem introduzir paginação agora.

#### 5. Estimativa de Tempo de Implementação
- **Minutos:** 150 (justificativa: escopo pequeno, sem schema e sem novas dependências, mas envolve serviço com 5 métodos, helpers, 4 páginas, include de menu, endpoint de fragmento, JS com debounce/race handling, validação de dump MySQL/MariaDB, checagens de compatibilidade PHP 8.3 e verificação manual dos casos de borda.)

#### 6. Diálogo Entre Modelos (Nota de Convergência - Máx 3 linhas)
- Concordo no essencial: plano é mínimo, DB-first e sem overengineering; não deve ser bloqueado por melhorias futuras.
- Alerta prioritário: validar `categoria_id`, capturar `Exception` nas páginas/`resultados.php` e confirmar collation/compatibilidade MySQL 8.0/PHP 8.3.
- Se outro parecerista pedir `LIMIT`, full-text ou testes automatizados extensos, tratar como evolução, não como bloqueio.

---

## Parecer: `meta/muse-spark-1.3-contributor`

### VEREDITO: APROVADO

- **APROVADO**: O plano está sólido, viável e pragmático, pronto para execução imediata sem bloqueadores técnicos reais.
- **RESSALVAS**: O plano possui falha técnica concreta ou risco real em produção que precisa ser ajustado antes de iniciar a codificação (não utilize para preciosismos ou "seria bom ter").
- **REJEITADO**: O plano possui falhas críticas conceituais, complexidade excessiva injustificada ou omissões graves que impedem o início seguro.

#### 1. Furos de Arquitetura & Premissas Frágeis
- Nomenclatura `listarTodas()` público vs `listarTodos()` admin difere em 1 letra: funciona, mas é armadilha real de manutenção; mitigado apenas por documentação no `AGENTS.md`.
- Contrato de `buscarPorTermo()` não explicita guard de termo vazio após `trim`: sem guard em `resultados.php`, `LIKE '%%'` retornaria as 11 notícias e quebraria o contrato `vazio/GET -> Sem notícias`.
- Tratamento de exceção nas páginas públicas fora do cabeçalho não explicitado: serviços lançam `Exception` genérica; se `index.php/noticia.php/categoria` não fizerem `try/catch`, vira fatal 500 em falha de banco.
- `listarDestaques()` sem `LIMIT`: fiel ao original (6 hoje), mas sem teto se volume crescer; premissa implícita de volume didático baixo.
- Validação de `categoria_id` em `noticias-por-categoria.php` não detalhada: `id` ausente/`abc` deve usar `Utils::verificarId()` como em `noticia.php`, senão `int 0` cai em "Não há notícias" em vez de 302.

#### 2. Omissões Críticas & Casos de Borda
- Nenhuma omissão bloqueante. Cobertos: `id` inválido -> 302, `id` inexistente -> alert, autor `NULL` -> "Equipe Microblog", categoria vazia/inexistente -> warning, `try/catch` no menu, XSS sem ecoar termo, SQL injection via prepared.
- Faltas menores não bloqueantes: não define `trim()` + early-return antes de chamar `buscarPorTermo`; não define escape de `imagem/titulo/resumo` em atributos; não define comportamento de `resultados.php` sob exceção de banco (deveria retornar fragmento `Sem notícias` + log, não 500 HTML).
- Sem paginação/`LIMIT` em `listarTodas()`/`buscarPorTermo()`: aceitável para 11 linhas, não é risco atual.

#### 3. Complexidade Excessiva & Oportunidades YAGNI
- Nenhum overengineering: 5 métodos de leitura, sem camadas novas, sem dependência JS, sem alteração de schema/CSS, sem event/caching/abstração prematura.
- Não simplificar para método genérico único: a separação atual espelha permissões admin vs público e evita `if` de perfil no código público.
- `docs/this.project.md/prompts.md/resumo.md` como NEW são artefatos de entrega avaliativa, não inchaço técnico.

#### 4. Recomendações Cirúrgicas de Ajuste
- Travar em `resultados.php`: `$termo = trim($_POST['busca'] ?? '')`; se `''` ou `$_SERVER['REQUEST_METHOD'] !== 'POST'` retorna direto `<h2 class="fs-5 text-danger">Sem notícias</h2>` sem consultar banco.
- Travar em `noticias-por-categoria.php`: usar `Utils::verificarId($_GET['id'])` + `CategoriaServico::buscarPorId()` para nome do banco; manter bloco de lista vazio renderizado no caso vazio como no original.
- Garantir `try/catch` + `registrarLog` + fallback amigável em todas as páginas públicas, não só no cabeçalho; nunca expor `PDOException`.
- Manter `LIKE` sem escape de `%/_` e `ORDER BY data DESC` apenas para fidelidade ao original; documentar como decisão intencional, não como padrão para outros módulos.

#### 5. Estimativa de Tempo de Implementação
- **Minutos:** 90 (5 métodos PDO + 4 páginas + menu + busca fetch/debounce + verificação `php -l`, `node --check` e matriz de 8 casos; escopo didático sem migração nem admin)

#### 6. Diálogo Entre Modelos (Nota de Convergência - Máx 3 linhas)
- Converjo que plano é fiel ao original e DB-first sem hardcode; único ponto a vigiar pelos pares é guard de termo vazio/GET e `try/catch` nas páginas. Não vejo base para RESSALVAS/REJEITADO.


---

## Parecer: `xiaomi/mimo-v2.6-pro`

### VEREDITO: APROVADO

- **APROVADO**: O plano resolve a área pública de ponta a ponta, respeita o ambiente (PHP 8.1+ enums, MySQL 8.0), segue DB-first, usa prepared statements e escape de saída, e mantém o escopo estritamente funcional. As lacunas apontadas abaixo são de robustez/edge-case, não bloqueadores de codificação.

#### 1. Furos de Arquitetura & Premissas Frágeis
- `js/busca.js` trata erro de **rede** (fetch rejeitado), mas não trata resposta HTTP de erro (500/404): `fetch` resolve com `response.ok === false`. Se `resultados.php` lançar `Exception` (falha de banco), o corpo HTML do erro será injetado na área de resultados — o plano não declara `if (!response.ok) throw`.
- `includes/todas.php` depende do autoload já ter sido carregado pela página chamadora (via `cabecalho.php`). Contrato implícito não documentado no plano; include fora dessa ordem quebra silenciosamente.
- Ordenação `ORDER BY data DESC` sem desempate: com timestamps idênticos (importação do dump) a ordem é não-determinística entre execuções — a matriz exige contagens exatas (6/11) e comparação de markup com o site de referência.
- `Utils::alertaErro` usa `history.back()`: para acesso direto a `noticia.php?id=99999` (sem histórico) o usuário fica em tela sem navegação. Comportamento do site de referência não foi verificado nesse caso específico.
- Dump gerado em MariaDB 10.4 importado em MySQL 8.0.46: premissa de compatibilidade (sql_mode, `utf8` vs `utf8mb4`, `zero dates`) assumida como resolvida sem nota de verificação de collation/charset na importação.

#### 2. Omissões Críticas & Casos de Borda
- Falha de banco em `resultados.php` não está na matriz de verificação nem no contrato de resposta (o endpoint é fragmento: qual HTML devolve em erro?).
- `categoria_id` inexistente vs. categoria sem notícias são tratados igual ("Não há notícias"), mas o plano não explicita como o nome da categoria é omitido nesse caso — validação de que o `<h2>` do badge não renderiza com nome vazio.
- Termo de busca com apenas espaços após `trim` (ex.: `"   "`) não está explícito na matriz (está implícito em "vazio"); idem para termo com `LIKE` pattern (`_`) — `%` está mapeado, `_` não.
- `noticia.php` com `id` negativo ou `0` (não apenas `abc`/ausente) depende de `Utils::verificarId`; o plano não confirma que o helper cobre esses casos — é premissa sobre código pré-existente.
- Contrato entre `resultados.php` (fragmento) e navegação sem JS: sem JS, o form do cabeçalho não tem `action` de fallback declarado.

#### 3. Complexidade Excessiva & Oportunidades YAGNI
- Nada a cortar. Escopo disciplinado: zero dependências novas, zero abstrações criadas, `formataDataIso()`/`nomeAutor()` são helpers pontuais reutilizáveis. Par `listarTodas()`/`listarTodos()` é ruído de nomenclatura, mas cortar agora custaria mais do que documentar (decisão correta).
- A matriz de verificação com casos de string exata ("zzzzz", `%`, ` ANDROID `) é adequada ao contrato de referência, não é over-testing.

#### 4. Recomendações Cirúrgicas de Ajuste
- Em `js/busca.js`: adicionar `if (!response.ok) throw new Error(...)` antes de injetar HTML no DOM; manter o estado de erro existente.
- Em `resultados.php`: envolver a chamada do serviço em `try/catch` e devolver o mesmo fragmento `<h2 class="fs-5 text-danger">Sem notícias</h2>` (ou um erro controlado curto) em falha de banco, logando via `Utils::registrarLog`.
- Fixar desempate de ordenação: `ORDER BY data DESC, id DESC` nos 5 métodos de leitura pública (não altera contrato de saída, elimina não-determinismo).
- Documentar em `AGENTS.md` o pré-requisito de autoload em `includes/todas.php` e reconciliar a divergência de versão de PHP (README diz 8.5+, plano diz 8.3/8.4) — doc-only.
- Confirmar no pós-importação do dump `SHOW VARIABLES LIKE 'character_set%'` / collation de `microblog_2026` (uma linha no passo F0).

#### 5. Estimativa de Tempo de Implementação
- **Minutos:** 200 (5 métodos de serviço + helpers ≈ 55; cabecalho/menu + 4 páginas públicas ≈ 65; `resultados.php` + `js/busca.js` ≈ 45; verificação estático/caixa-preta + comparação de referência ≈ 35; commits e documentação ≈ 25 — descontado o fato de já estar implementado, é o custo de reexecução honesto).

#### 6. Diálogo Entre Modelos (Nota de Convergência - Máx 3 linhas)
Concordo com a arquitetura e o corte de escopo; meu único alerta prioritário é o `response.ok` ausente no `busca.js` — erro HTTP vira HTML injetado no DOM. Discordo de quem exigir rollback/transação: são só operações de leitura, não há risco de perda de dados. Questiono apenas a determinismo da ordenação sem `id` como desempate.
