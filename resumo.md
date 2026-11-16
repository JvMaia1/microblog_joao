# Resumo do histórico da branch

Branch atual: `09_aula-1611`

Base: `08_aula-1311`

Período das alterações desta branch: 16/11/2026

## Funcionalidade herdada

A branch parte do trabalho anterior de autenticação e autorização por sessão,
gerenciamento de usuários e categorias, cadastro de notícias e listagem de
notícias conforme o perfil. Administradores podem consultar notícias de todos
os autores; editores veem as próprias notícias.

## Alterações nesta branch

1. **Organização das imagens (`45e08c4`)**
   As imagens mantidas pelo site foram movidas para `images/`; imagens não
   utilizadas foram removidas de `_materiais/Fotos/`. Essa organização
   corresponde ao diretório usado pelas páginas e pelo helper de upload.

2. **Configuração local e dependências (`f699fca`)**
   Foi atualizada a configuração de conexão com o banco para o ambiente local e
   adicionado o `composer.lock`. Não copie configurações locais ou credenciais
   para outros ambientes sem revisá-las.

3. **Guia para desenvolvimento assistido (`72e2236`)**
   Foi criado `AGENTS.md` com a arquitetura, padrões de código, configuração,
   regras de acesso, validação e estado conhecido das funcionalidades, servindo
   como referência para futuras alterações.

4. **Carregamento de notícia para edição (`b149c1f`)**
   `NoticiaServico::buscarPorId()` carrega uma notícia pelo ID, incluindo seus
   dados e autor. Administradores podem buscar notícia de qualquer autor;
   editores só recebem notícias associadas ao próprio ID de usuário.
   `admin/noticias.php` já envia o ID em `noticia-atualiza.php?id=...`.
   A página de edição usa esse ID para preencher os campos, exibe a imagem
   atual, carrega todas as categorias e seleciona a categoria da notícia.
   O formulário não persiste alterações: a atualização ainda não foi
   implementada.

## Estado atual relevante

- `NoticiaServico` tem listagem conforme o perfil, busca protegida por ID e
  inserção. Não presumir que atualização ou exclusão de notícia estejam
  implementadas.
- `admin/noticia-atualiza.php` carrega e exibe dados; o botão de atualização
  permanece desabilitado até a implementação do salvamento.
- `admin/noticia-exclui.php` ainda é provisória.
- Algumas páginas públicas ainda contêm conteúdo estático provisório.
- A página de edição passou pela verificação de sintaxe do PHP após as
  alterações.
