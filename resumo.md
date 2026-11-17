# Resumo do histórico da branch

Branch atual: `10_aula-1711`

Base: `09_aula-1611`

Período das alterações desta branch: 17/11/2026

## Funcionalidade herdada

A branch parte do trabalho anterior de autenticação e autorização por sessão,
gerenciamento de usuários e categorias, cadastro de notícias e listagem de
notícias conforme o perfil. Administradores consultam notícias de todos os
autores; editores veem as próprias notícias.

As páginas públicas, o menu de categorias e a busca continuavam com conteúdo
provisório estático. O `js/busca.js` estava vazio (apenas `'use strict';`).

## Alterações nesta branch

1. **Consultas da área pública (`2520b28`)**
   `NoticiaServico` ganhou os métodos `listarDestaques()`, `listarTodas()`,
   `listarPorCategoria()`, `buscarPorIdPublico()` e `buscarPorTermo()`. São
   métodos de leitura sem sessão/perfil, que seguem o padrão de erro dos
   serviços existentes (`PDOException` registrada em log e `Exception` genérica
   para a interface). Os métodos administrativos não foram alterados.

2. **Menu de categorias dinâmico (`ef1b3a7`)**
   `includes/cabecalho.php` passou a carregar o autoload e a montar o menu com
   todas as categorias do banco (`CategoriaServico::listarTodos`). Falha de
   conexão é registrada em log e mantém o menu vazio, sem derrubar a página.

3. **Páginas públicas dinâmicas (`df35006`)**
   `index.php` exibe as notícias em destaque (imagem, título, resumo e link
   dinâmico), `includes/todas.php` lista todas as notícias, `noticia.php` exibe
   o detalhe (data, autor, imagem e texto) e `noticias-por-categoria.php` lista
   as notícias da categoria escolhida no menu. `Utils` ganhou `formataDataIso()`
   (atributo `datetime` do elemento `<time>`) e `nomeAutor()`, que centraliza a
   regra de exibição "Equipe Microblog" quando a notícia não tem autor.

4. **Busca de notícias (`01f27c1`)**
   `resultados.php` responde à busca com um fragmento HTML (termo normalizado,
   limite de 100 caracteres e `prepared statement` consultando título, resumo e
   texto). `js/busca.js` consulta o endpoint com `fetch` + `FormData`, debounce
   de 300 ms, `aria-busy`, descarte de respostas antigas e mensagem de erro.

## Estado atual relevante

- As páginas públicas não têm mais conteúdo provisório: todas renderizam dados
  do banco e usam escape de saída (`Utils::escaparHtml`).
- `noticia.php` e `noticias-por-categoria.php` validam o id com
  `Utils::verificarId()`; id inválido redireciona para `index.php` e id válido
  sem registro em `noticia.php` dispara `Utils::alertaErro()`.
- A página de categoria sem notícias (ou categoria inexistente) exibe
  "Não há notícias desta categoria".
- A busca não pesquisa o nome do autor e não trata `%`/`_` como literais (mesmo
  comportamento do site de referência).
- Atualização e exclusão de notícias continuam sem implementação no serviço, e
  `admin/noticia-exclui.php` segue provisória.

## Verificação executada nesta branch

- `php -l` em todos os arquivos PHP alterados e `node --check` em
  `js/busca.js`.
- Comparação de comportamento e de markup com o site de referência
  (<https://microblog-tec.tiagohub.com/>): cards de destaque, item da lista,
  detalhe da notícia, página de categoria e casos de erro da busca.
- Teste com notícia temporária sem autor (`usuario_id` nulo) para validar o
  texto "Equipe Microblog" nas duas páginas que exibem autor.
- Teste com categoria inserida no banco para confirmar que o menu é dinâmico.
- Busca exercitada no navegador: resultados após a pausa de digitação, clique
  abrindo a notícia correta, campo vazio escondendo a área, sem erro de console.

## Ambiente

O banco local foi recriado a partir de `_materiais/microblog_2026_1611.sql` em
um banco chamado `microblog_2026`. A classe `ConexaoBD` está marcada como
*skip-worktree* neste repositório (credenciais e nome do banco são de cada
ambiente), portanto esse ajuste não faz parte dos commits: revise os valores
antes de rodar o projeto em outra máquina.
