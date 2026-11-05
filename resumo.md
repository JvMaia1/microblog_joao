# Resumo do histórico da branch

Branch analisada: `03_aula-0511`

Período dos commits: 03/11/2026 a 05/11/2026

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

## Estado resultante

A branch inclui listagem e cadastro persistidos de usuários, além do fluxo de edição que busca os dados existentes, valida as alterações e as persiste. As senhas em branco permanecem inalteradas; novas senhas são validadas e codificadas. Falhas técnicas na listagem, no cadastro, na busca ou na atualização são registradas no log e apresentadas ao usuário por mensagens apropriadas.
