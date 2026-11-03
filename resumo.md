# Resumo do histórico da branch

Branch analisada: `01_aula-0311`  
Período dos commits: 03/11/2026

## Principais pontos trabalhados

1. **Estrutura inicial do Microblog (`8d19eeb`)**  
   Foi criado o portal didático em PHP para notícias, com áreas pública e administrativa, telas e operações de gestão, conexão PDO com MySQL/MariaDB, modelos, enums, helpers, materiais SQL e conteúdo inicial.

2. **Namespaces e autoload do Composer (`bdc25f5`)**  
   Foi configurado o mapeamento PSR-4 `Microblog\\` para `src/`, adicionados namespaces às classes e enums e incluído `vendor/` no `.gitignore`.

3. **Serviço de usuários (`b248e13`, `c3d5740`)**  
   Foi criado `UsuarioServico`, ligado à conexão compartilhada do banco, e implementado `listarTodos()` para buscar usuários ordenados pelo nome. Erros PDO são registrados e convertidos em uma exceção com mensagem genérica.

4. **Integração da listagem administrativa (`e01e6e1`)**  
   A página `admin/usuarios.php` passou a carregar os dados pelo serviço e apresentar um alerta amigável se a consulta falhar.

5. **Ajuste da tabela consultada (`51926b1`)**  
   A consulta foi corrigida para usar a tabela `usuarios`, conforme o esquema do projeto.

6. **Exibição de dados reais (`8398959`)**  
   Os registros de exemplo foram substituídos pelos usuários retornados do banco. A tela agora mostra a contagem dinâmica, escapa os valores apresentados e só exibe a tabela quando o carregamento é bem-sucedido.

## Estado resultante

A branch evoluiu de uma base funcional de portal de notícias para incluir o carregamento dos usuários reais na tela administrativa, com tratamento de falha e integração via serviço. O foco dos commits após a estrutura inicial foi a listagem de usuários; não houve, nesse histórico, implementação completa das demais operações de persistência.
