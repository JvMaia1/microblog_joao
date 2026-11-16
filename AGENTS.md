# Guia de desenvolvimento do Microblog

Este arquivo registra o contexto técnico do projeto e orientações para futuras
alterações, inclusive em sessões sem histórico de conversação.

## Visão geral

Microblog é um portal didático de notícias feito em PHP, com páginas públicas e
um painel administrativo. O código combina páginas PHP tradicionais com classes
orientadas a objetos, MySQL/MariaDB via PDO e HTML/Bootstrap.

O idioma do projeto é português: nomes de classes, métodos, variáveis, mensagens
e conteúdo de interface seguem essa convenção.

## Estrutura

- `src/Auth/`: autenticação por sessão e autorização por papel.
- `src/Database/`: conexão PDO compartilhada.
- `src/Enums/`: tipos de usuário e valores de destaque.
- `src/Helpers/`: normalização, escape, upload, logs e validações.
- `src/Models/`: modelos `Usuario`, `Categoria` e `Noticia`.
- `src/Services/`: acesso a dados e operações de negócio usando PDO.
- `admin/`: páginas e formulários do painel.
- `includes/`: cabeçalhos, rodapés e conteúdo compartilhado.
- `css/`, `js/`, `admin/js/`, `images/`: recursos estáticos.
- `_materiais/`: scripts SQL e material de referência/aula.
- `logs/erros.log`: log de erros da aplicação.
- `vendor/`: autoload do Composer; não editar manualmente.

As páginas públicas ficam na raiz: `index.php`, `noticia.php`,
`noticias-por-categoria.php`, `resultados.php` e `login.php`.

## Ambiente e execução

- O `README.md` documenta PHP 8.5 ou superior, extensões `pdo_mysql`,
  `mbstring` e `fileinfo`, servidor web local e MySQL/MariaDB.
- O código usa enums nativos, portanto requer uma versão de PHP com suporte a
  enums.
- O Composer configura o prefixo PSR-4 `Microblog\` para `src/`.
  As páginas carregam `vendor/autoload.php` com `require_once`.
- Importe `_materiais/microblog_2026_completo.sql` para criar/preencher o banco
  `microblog_2026`. O script `_materiais/microblog_2026_inicial.sql` é uma
  alternativa inicial. `modelagem-fisica.md` contém uma modelagem antiga,
  com o nome de banco `microblog_2025`; não a trate como fonte atual do banco.
- A conexão está em `src/Database/ConexaoBD.php`. Ela usa atualmente
  `localhost`, usuário `root`, senha vazia e banco `microblog_2026`; adapte as
  configurações ao ambiente local e não adicione credenciais reais ao
  repositório.
- Não há scripts de teste ou ferramentas de lint configurados no `composer.json`.
  Para validar PHP alterado, use `php -l caminho/do/arquivo.php` quando o PHP
  estiver disponível.

## Arquitetura e convenções

### Namespaces e nomes

- Use `Microblog\` e organize o namespace de acordo com o diretório, por
  exemplo `Microblog\Services` para `src/Services`.
- Classes e enums usam PascalCase; métodos e variáveis usam camelCase.
- Mantenha nomes em português e os termos já estabelecidos, como `Servico`,
  `Noticia`, `Usuario`, `categoriaId` e `mensagemErro`.
- Classes de serviço e modelos existentes são `final`. Modelos têm propriedades
  privadas tipadas, construtores e getters `get...()`.
- Preserve tipos explícitos, incluindo retornos (`: void`, `: array`,
  `: ?array`, etc.). Use `?int`/`?string` para valores opcionais quando
  necessário.

### Modelos, enums e banco

- `Usuario` contém nome, e-mail, senha, tipo (`TipoUsuario`) e id opcional.
- `Categoria` contém nome e id opcional.
- `Noticia` contém título, texto, resumo, nome da imagem, destaque (`Destaque`),
  id do usuário, id da categoria, id opcional e data opcional. O enum deve ser
  convertido para seu valor persistido com `->value`.
- Os enums atuais são `TipoUsuario::ADMIN`/`EDITOR` (`admin`/`editor`) e
  `Destaque::SIM`/`NAO` (`sim`/`nao`).
- O schema atual tem `usuarios`, `categorias` e `noticias`; as tabelas usam
  ids auto incremento. O e-mail do usuário é único. A data da notícia tem
  `CURRENT_TIMESTAMP` como padrão. A notícia referencia usuário e categoria,
  com `ON DELETE SET NULL`.
- As consultas e colunas são em snake_case, por exemplo `usuario_id` e
  `categoria_id`. Use parâmetros nomeados e prepared statements para valores
  fornecidos pelo usuário.

### Serviços e erros

- Os serviços obtêm PDO com `ConexaoBD::getConexao()` no construtor.
- Métodos seguem os nomes já usados: `listarTodos`, `buscarPorId`,
  `buscarPorEmail`, `inserir`, `atualizar` e `excluir`.
- Em operações PDO, capture `PDOException`, registre os detalhes com
  `Utils::registrarLog($e)` e lance uma `Exception` com mensagem genérica para
  a interface. Não exiba detalhes internos do banco ao usuário.
- As páginas normalmente inicializam mensagens/listas, executam a operação em
  `try/catch`, exibem feedback amigável e escapado e redirecionam após sucesso.
  Use `InvalidArgumentException` para erros de validação; registre falhas
  inesperadas em log e não as oculte com valores falsos de sucesso.
- Evite duplicar operações de banco nas páginas: use a classe de serviço
  apropriada.

### Entrada, validação e saída

- Normalize dados de formulários com `Utils::normalizarEntrada()`. O modo
  padrão remove espaços nas extremidades de strings; `'email'` também aplica
  `trim`; `'inteiro'` valida inteiro e retorna `0` se inválido.
- IDs vindos de URL devem passar por `Utils::verificarId()`, que exige inteiro
  positivo ou redireciona.
- Valide campos com os métodos existentes em `Validacoes`, antes de criar
  modelos/persistir. Use `TipoUsuario::tryFrom()` e `Destaque::tryFrom()` na
  validação de valores externos; depois da validação, `::from()` cria o enum.
- Escape dados dinâmicos na saída HTML com `Utils::escaparHtml()`, inclusive
  conteúdo em atributos e mensagens. Não confunda normalização de entrada com
  escape de saída.
- `Utils::registrarLog()` aceita `Throwable` e grava em `logs/erros.log`.
- Senhas novas são processadas por `Utils::codificarSenha()`/`password_hash`;
  use `password_verify()` para autenticar. Na atualização, campo de senha vazio
  mantém o hash atual; `Utils::verificarSenha()` evita re-hash quando a senha
  informada já corresponde ao hash atual.
- `Utils::upload()` recebe a entrada de `$_FILES`, exige upload válido, limita
  o arquivo a 2 MB, verifica MIME e extensão (JPEG, GIF, PNG ou WebP), limita
  o nome a 60 caracteres e salva em `images/`. Atualmente usa o nome original
  e pode sobrescrever um arquivo de mesmo nome; considere esse comportamento
  ao alterar o fluxo.

### Acesso administrativo

- Inclua o autoload e aplique `ControleDeAcesso::exigirLogin()` ou
  `ControleDeAcesso::exigirAdmin()` antes de processar/exibir uma página
  protegida. A autorização deve ser aplicada na página/ação, não apenas
  escondendo links na navegação.
- O tipo da sessão é comparado com `TipoUsuario::ADMIN->value`.
- O comportamento implementado é: editor acessa o painel e gerencia as próprias
  notícias e o perfil; administrador pode gerenciar usuários, categorias e
  notícias de todos os autores. As páginas de categorias e usuários exigem
  admin, embora trechos de documentação antiga descrevam permissões de forma
  diferente.
- A exclusão da própria conta é impedida em `admin/usuario-exclui.php`.

## Estado funcional conhecido

- `UsuarioServico` e `CategoriaServico` oferecem listagem, busca, inserção,
  atualização e exclusão.
- `NoticiaServico` tem `listarTodos(TipoUsuario $tipoUsuario, int $usuarioId)`,
  que retorna todas as notícias com autor para admin e apenas as do usuário
  informado para editor, além de `inserir(Noticia $noticia)`. Não presumir que
  busca, atualização ou exclusão de notícia já estejam implementadas.
- `admin/noticia-insere.php` integra categorias, validação, upload e inserção.
- `admin/noticia-atualiza.php` e `admin/noticia-exclui.php` ainda são páginas
  provisórias; não as trate como fluxos completos.
- As páginas públicas (`index.php`, `noticia.php`,
  `noticias-por-categoria.php` e `resultados.php`) e alguns conteúdos de
  navegação/listagem ainda contêm dados ou marcação provisórios estáticos.
  Confira a implementação atual antes de assumir que o README descreve
  funcionalidades dinâmicas já concluídas.

## Orientações para alterações

- Leia os arquivos relacionados antes de mudar código e siga os padrões
  existentes; faça alterações pequenas e diretamente ligadas ao pedido.
- Preserve os limites entre páginas, serviços, modelos e helpers.
- Mantenha autorização no servidor, prepared statements para SQL e escape no
  ponto de saída HTML.
- Atualize este guia ou o `README.md` quando uma mudança alterar
  significativamente a arquitetura, configuração ou estado funcional descrito.
- Não altere arquivos gerados em `vendor/` nem reverta mudanças preexistentes
  que não façam parte da tarefa.
