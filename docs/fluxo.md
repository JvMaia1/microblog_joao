# Fluxo — Área pública do Microblog + busca

> Documento vivo do estado atual após a entrega da área pública dinâmica e do
> sistema de busca. Atualizar quando houver mudança de arquitetura, novo endpoint
> ou alteração no fluxo principal.

## 1. Visão Geral de Contexto

> Como a área pública se insere no ecossistema do projeto.

```mermaid
graph LR
    Visitante["👤 Visitante<br/><i>navegador, sem login</i>"]
    Publico["🧩 Área pública<br/><i>index, noticia, categoria, busca</i>"]
    Admin["⚙️ Área administrativa<br/><i>login por sessão</i>"]
    DB["🗄️ MySQL/MariaDB<br/><i>noticias, categorias, usuarios</i>"]
    CDN["🌐 CDN Bootstrap<br/><i>CSS/JS de layout</i>"]

    Visitante -->|"HTTP GET/POST"| Publico
    Publico -->|"Consulta (PDO)<br/>classes de serviço"| DB
    Publico -->|"Carrega layout"| CDN
    Admin -->|"Grava notícias/categorias/usuários"| DB
    Admin -->|"Publica"| Publico
```

## 2. Fluxo da Solução

> Caminho percorrido pelo dado nas duas jornadas implementadas: navegação de
> conteúdo e busca viva.

```mermaid
sequenceDiagram
    actor V as 👤 Visitante
    participant P as 🖥️ Página pública
    participant S as ⚙️ Serviço (NoticiaServico)
    participant D as 🗄️ Banco de dados
    participant J as 🔎 js/busca.js
    participant R as 🧩 resultados.php

    Note over V,D: Jornada 1 — navegação de conteúdo
    V->>P: GET index.php
    P->>S: listarDestaques() / listarTodas()
    S->>D: SELECT ... WHERE destaque = 'sim' / ORDER BY data DESC, id DESC
    D-->>S: linhas
    S-->>P: arrays com autor (LEFT JOIN)
    P-->>V: cards + listagem (valores escapados)
    V->>P: GET noticia.php?id=23
    P->>P: Utils::verificarId($_GET['id'])
    P->>S: buscarPorIdPublico(23)
    S->>D: SELECT ... WHERE id = :id
    D-->>S: linha ou vazio
    S-->>P: dados da notícia (autor pode ser nulo)
    P-->>V: título, data, autor (ou "Equipe Microblog"), imagem, texto

    Note over V,R: Jornada 2 — busca viva no cabeçalho
    V->>J: digita "android" no campo
    J->>J: debounce 300 ms + número da consulta
    J->>R: POST busca=android (FormData)
    R->>R: normalizarEntrada() + limite de 100 caracteres
    R->>S: buscarPorTermo("android")
    S->>D: SELECT ... WHERE titulo LIKE %..% OR resumo LIKE %..% OR texto LIKE %..%
    D-->>S: linhas
    S-->>R: id e título
    R-->>J: fragmento HTML (badge + list-group)
    J->>J: descarta resposta obsoleta (número != buscaAtual)
    J-->>V: resultados na área #resultados (aria-busy controlado)
```

## 3. Especificação Técnica

> Componentes envolvidos e os contratos usados pelas páginas públicas.

```mermaid
classDiagram
    class NoticiaServico {
        -PDO conexao
        +listarDestaques() array
        +listarTodas() array
        +listarPorCategoria(categoriaId) array
        +buscarPorIdPublico(id) ?array
        +buscarPorTermo(termo) array
        +listarTodos(tipoUsuario, usuarioId) array
        +buscarPorId(id, tipoUsuario, usuarioId) ?array
        +inserir(Noticia) void
    }
    class CategoriaServico {
        +listarTodos() array
        +buscarPorId(id) ?array
    }
    class Utils {
        +escaparHtml(valor) string
        +verificarId(valor) void
        +normalizarEntrada(entrada, tipo)
        +formataData(data) string
        +formataDataIso(data) string
        +nomeAutor(autor) string
        +registrarLog(Throwable) void
    }
    class Noticia {
        +int id
        +string data
        +string titulo
        +string texto
        +string resumo
        +string imagem
        +Destaque destaque
        +int usuarioId
        +int categoriaId
    }
    class Destaque {
        <<enumeration>>
        SIM
        NAO
    }

    NoticiaServico --> Noticia : insere
    NoticiaServico --> Destaque : usa SIM->value
    NoticiaServico --> Utils : log de erro
    CategoriaServico --> Utils : log de erro
```

### Tabela de Handoff (Contratos das páginas públicas)

| Rota / Endpoint | Método | Entrada | Saída | Arquivo |
|---|---|---|---|---|
| `index.php` | GET | — | HTML com 1 card por notícia em destaque (imagem, título, resumo, link) + listagem "Todas as notícias" | `index.php` + `includes/todas.php` |
| `noticia.php` | GET | `id` (int ≥ 1) na query string | HTML do detalhe; `id` inválido → 302 `index.php`; id inexistente → `alert('Notícia não encontrada.')` | `noticia.php` |
| `noticias-por-categoria.php` | GET | `id` (int ≥ 1) da categoria | HTML com badge do nome da categoria e itens (título, data, autor, resumo); vazio → "Não há notícias desta categoria" | `noticias-por-categoria.php` |
| `resultados.php` | POST | `busca` (texto, até 100 caracteres após normalização) | Fragmento HTML: `<h2>Resultados: badge N</h2>` + `list-group`, ou `<h2 class="fs-5 text-danger">Sem notícias</h2>`, ou alerta de erro | `resultados.php` |
| `includes/cabecalho.php` | — (include) | — | Menu de categorias do banco (`noticias-por-categoria.php?id=N`) | `includes/cabecalho.php` |
| `js/busca.js` | — (cliente) | evento `input` no `#campo-busca` | `fetch POST` em `resultados.php`; injeta o fragmento em `#resultados`; erro de rede/HTTP → `p.alert.alert-warning` | `js/busca.js` |
