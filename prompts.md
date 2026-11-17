# Histórico de prompts — Finalização do Microblog: área pública + busca

Atividade: programar o funcionamento das páginas públicas do Microblog
(`index.php`, `noticia.php`, `noticias-por-categoria.php`, `includes/todas.php`)
e o desafio do sistema de busca (`resultados.php` + `js/busca.js`).

Branch: `10_aula_1711` (base `09_aula-1611`) — 17/11/2026

Este arquivo registra **as mensagens que eu enviei** ao longo da atividade, na
ordem em que foram feitas, com o efeito de cada uma. Prompts usados em outras
ferramentas (ex.: Copilot no VSCode) devem ser acrescentados aqui.

---

## 1. Abertura da atividade e pedido de plano

```text
a principio vamos elaborar um plano para podermos fazer essa atividade, vou te
passar o enunciado e vamos desenvolver isso como eu faria no meu ambiente de
trabalho, baseado no meu workflow, e priorizando o que a atividade pede com
qualidade e compreensao de codigo
```

(na mesma mensagem, o enunciado abaixo)

```text
Finalização do Microblog: elaborar prompts e programar a área pública

Com o auxílio da IA dentro do VSCode, você deve programar o funcionamento das
páginas públicas do Microblog, sendo:

index.php:
- Exibição das notícias que são destaque, mostrando a imagem, o título e o
  resumo, e com o link dinâmico configurado para abrir a página noticia.php
  quando clicar em cima da notícia
- Exibição de todas as notícias, com a programação sendo feita no arquivo
  includes/todas.php

noticia.php:
- Exibição da notícia e seus detalhes: data (formatada para dia/mês/ano),
  autor, imagem e texto
- No caso do autor, caso a notícia não tenha um autor associado a ela, deve ser
  exibido como alternativa o texto "Equipe Microblog"

noticias-por-categoria.php:
- Exibição das notícias filtradas pela categoria escolhida no menu do site
- Deve ser exibido: título, data (formatada para dia/mês/ano), autor e resumo.
- No caso do autor, caso a notícia não tenha um autor associado a ela, deve ser
  exibido como alternativa o texto "Equipe Microblog"
- Também configure o link dinâmico para abrir a página noticia.php quando
  clicar em cima da notícia

Atenção: primeiro, programe o arquivo includes/cabecalho.php para fazer com que
o menu mostre todas as categorias existentes

🔥DESAFIO: sistema de busca
Neste desafio, você precisará programar os arquivos resultados.php e busca.js
Portanto, para ter o mesmo comportamento visto no site Microblog original, você
deverá programar PHP e JavaScript
O PHP programado em resultados.php continuará sendo o responsável pela
comunicação com as classes operações no banco de dados
E o JS programado em busca.js fará a comunicação com resultados.php usando
fetch e as interações necessárias no Front-End

Site original para referência de funcionamento: https://microblog-tec.tiagohub.com/

Entrega:
- Envie aqui o link direto para a branch em que fez a atividade
- Envie aqui o arquivo Markdown com todo o histórico organizado de prompts
```

**Efeito:** o projeto foi lido (AGENTS.md, classes de `src/`, páginas públicas,
banco local) e o plano foi elaborado antes de qualquer código. No caminho,
descobriu-se que o banco local estava com schema antigo — incompatível com o
código atual — e que o dump canônico era `_materiais/microblog_2026_1611.sql`.
As decisões alinhadas (banco, formato de data, branch, divisão de trabalho)
estão em `docs/this.project.md`; o plano, em `implementation_plan.md`.

---

## 2. Cobrança da etapa do Plan Council

```text
noto que nao passamos pelo plan council
```

**Efeito:** o plano (`implementation_plan.md`) e o contexto (`docs/this.project.md`)
foram submetidos ao conselho multi-modelo em modo `plan`. Veredito: **APROVADO**
por consenso (3/3), com o parecer em `docs/council_report.md` e o diálogo entre
os modelos anexado ao fim do plano. Das ressalvas apontadas, uma foi aplicada
(desempate da ordenação por `id`) e as demais foram conferidas no código.

---

## 3. Entrada no modo Builder

```text
e vamos entrar no modo bulder
```

**Efeito:** a esteira de produção passou a ser seguida — auditoria independente
do código (subagente `sweeper`, read-only), com verificação de falha de banco,
payloads de XSS/SQLi e ids inválidos. Selo obtido: `SWEEPER APPROVED`.

---

## 4. Dispensa da suíte de testes

```text
podemos pular os testes
```

**Efeito:** a suíte formal em `/tests/` não foi criada (registrado no plano). A
verificação permaneceu a validação manual já executada: `php -l` em todos os
arquivos PHP alterados, `node --check` no `js/busca.js`, comparação de markup e
comportamento com o site de referência, navegador real (digitação e clique na
busca) e os casos de borda descritos em `resumo.md`.

---

## 5. Correção deste arquivo

```text
o arquivo de prompts deveria conter apenas os prompts que eu dei, ali contem
prompts que eu nao escrevi
```

**Efeito:** este arquivo foi reescrito para conter apenas as mensagens enviadas
por mim nesta atividade (itens 1 a 5), preservando o histórico anterior da
branch `09_aula-1611` logo abaixo.

---

## Histórico anterior (branch `09_aula-1611`)

### 1. Pedir pra IA analisar o projeto no estado atual

Analise todas as classes utilizadas até o momento (contidas na pasta src), em especial
a classe NoticiaServico e a classe Noticia.

Veja também as páginas administrativas, em especial usuários.php e categorias.php.

Identifique padrões, como nomes de métodos, propriedades, variáveis, arquivos, namespaces.

Aguarde o próximo comando.

### 2. Criação do método listarTodos

Crie na classe NoticiaServico o método listarTodos, de maneira semelhante ao que foi feito em CategoriaServico e UsuarioServico.

Atenção: a consulta SQL deve no caso de usuários admin, carregar todas as noticias incluindo o nome do autor delas. Se for um usuário editor, a consulta deve carregar apenas as noticias dele próprio, sem incluir o nome do autor.

Tarefas:

- visualizar noticias e nome do autor delas (admin)
- visualizar notícias próprias (editor)
