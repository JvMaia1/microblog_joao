# Resumo do histórico da branch

Branch analisada: `04_exercicio-categorias`

Período dos commits: 03/11/2026 a 10/11/2026

## Principais pontos trabalhados

1. **Estrutura inicial do Microblog (`8d19eeb`)**  
   Foi criado o portal didático em PHP para notícias, com áreas pública e administrativa, telas e operações de gestão, conexão PDO com MySQL/MariaDB, modelos, enums, helpers, materiais SQL e conteúdo inicial.

2. **Namespaces e autoload do Composer (`bdc25f5`)**  
   Foi configurado o mapeamento PSR-4 `Microblog\\` para `src/`, adicionados namespaces às classes e enums e incluído `vendor/` no `.gitignore`.

3. **Serviço e listagem de usuários (`b248e13`, `c3d5740`, `e01e6e1`, `51926b1`, `8398959`, `651b4a0`)**
   Foi criado `UsuarioServico`, ligado à conexão compartilhada do banco, e implementada a consulta de usuários na tabela `usuarios`. A área administrativa passou a exibir os registros reais, com contagem dinâmica e escape dos valores. Falhas no carregamento são registradas no log e apresentadas ao usuário por uma mensagem genérica.

4. **Cadastro de usuários (`dffba45`, `1465307`, `5d7c2b8`, `7e83f75`, `3d1542e`)**
   O serviço ganhou `inserir(Usuario $usuario)`, usando parâmetros preparados e os dados obtidos pelos getters do modelo. O ID do modelo `Usuario` passou a ser anulável e opcional, para permitir a criação de contas sem ID prévio. O formulário administrativo normaliza e valida nome, e-mail, senha e tipo de conta; codifica a senha, converte o tipo para `TipoUsuario` e persiste o novo usuário. Em caso de sucesso, redireciona à listagem; erros de validação são exibidos e falhas inesperadas são registradas e comunicadas com mensagem genérica. Os campos de nome, e-mail e tipo são preservados após erros, e os valores apresentados são escapados.

5. **Busca e preenchimento do formulário de edição (`c23e544`, `40ffeb8`, `42f533a`)**
   `UsuarioServico` ganhou `buscarPorId(int $id)`, que consulta os dados da conta e retorna `null` quando não encontra registro; falhas de banco são registradas e convertidas em erro genérico. A página de edição normaliza e verifica o ID recebido pela URL, carrega a conta, informa quando não há usuário e preenche nome, e-mail e tipo com os dados encontrados, escapando os valores exibidos.

6. **Atualização de usuários (`8b6ee29`, `4aea467`, `d578a5f`)**
   `UsuarioServico` ganhou o método `atualizar(Usuario $usuario)`, que persiste nome, e-mail, senha e tipo por meio de uma instrução preparada e registra falhas de banco. O formulário de edição passou a processar envios POST, normalizar e validar nome, e-mail e tipo, criar o modelo e chamar o serviço; em caso de sucesso, redireciona à listagem. A senha atual é mantida quando o campo fica em branco. Quando preenchida, a nova senha é validada; se for igual à atual, mantém-se o hash existente, e, se diferente, é armazenada codificada. Erros de validação são apresentados, e falhas inesperadas são registradas e comunicadas com mensagem genérica.

7. **Listagem de categorias (`72307b8`, `af03442`, `fe506c2`, `905412c`)**
   Foi criado `CategoriaServico` com consulta ordenada pelo nome. A página administrativa passou a carregar e renderizar as categorias do banco, exibir a quantidade de registros e oferecer operações de atualização e exclusão. Os nomes e IDs exibidos são escapados; falhas ao carregar a lista são registradas e mostradas com uma mensagem genérica.

8. **Cadastro de categorias (`6f128ab`, `5436c83`, `b12a5c8`)**
   O serviço ganhou a inserção de categorias com instrução preparada. O ID de `Categoria` tornou-se opcional para permitir a criação de registros sem ID prévio. O formulário valida o nome, preserva o valor digitado em caso de erro e, quando há sucesso, redireciona à listagem. Erros de validação são apresentados; falhas inesperadas são registradas e informadas genericamente.

9. **Busca e atualização de categorias (`057e9eb`, `b15e495`, `d0377fc`, `b9ea650`)**
   `CategoriaServico` passou a buscar uma categoria pelo ID e atualizá-la usando instrução preparada. A tela de edição valida o ID recebido, carrega e preenche os dados existentes, valida o nome enviado e associa o ID ao modelo antes de persistir. Categorias inexistentes e falhas de carregamento ou atualização recebem tratamento apropriado, com registro de falhas técnicas.

10. **Exclusão de categorias e exibição de erros (`d49ae76`, `bbc241c`, `9c7bc27`)**
    O serviço ganhou a exclusão por ID com consulta preparada e registro de erros. A página de exclusão normaliza e verifica o ID, redireciona para a listagem quando a operação termina e registra e apresenta uma mensagem genérica em caso de falha. Também foi incluída na edição de usuários a exibição, com escape HTML, das mensagens de erro já geradas pelo processamento do formulário.

## Estado resultante

A branch inclui os fluxos administrativos de listagem, cadastro, edição e exclusão de categorias, além da listagem, cadastro e edição de usuários. Os formulários validam os dados, preservam os valores pertinentes após erros e exibem mensagens escapadas; as senhas em branco permanecem inalteradas e novas senhas são validadas e codificadas. As operações de persistência usam consultas preparadas, e falhas técnicas são registradas no log e comunicadas ao usuário por mensagens apropriadas.
