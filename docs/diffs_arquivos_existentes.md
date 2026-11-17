# Diffs Completos dos Arquivos Preexistentes — Microblog (área pública + busca)

Este documento consolida **todas as alterações (diffs unificados)** aplicadas
exclusivamente nos arquivos que **já existiam** no projeto antes desta entrega.
Arquivos 100% novos (`docs/this.project.md`, `docs/council_report.md`,
`implementation_plan.md`) não entram aqui por não possuírem versão legada.

- **Base de comparação (Git):** commit `6d509b9` (`09_aula-1611`)
- **Versão final (workspace):** HEAD da branch `10_aula-1711`
- **Reprodução exata:** `git diff 6d509b9..HEAD -- <arquivo>`
- O commit `831472d` normalizou a quebra de linha final dos arquivos
  reescritos; os blocos abaixo refletem o diff agregado da branch.

Arquivos de documentação do próprio projeto (`AGENTS.md`, `prompts.md`,
`resumo.md`) também foram atualizados, mas não têm diff reproduzido aqui: são
memoriais, e o diff completo está nos commits `389e21a` e `6445d6e`.

---

## 📑 Índice dos Arquivos Preexistentes

1. [`src/Services/NoticiaServico.php`](#1-dados--srcservicesnoticiaservicophp) — consultas da área pública
2. [`src/Helpers/Utils.php`](#2-utilitários--srchelpersutilsphp) — data ISO e nome do autor
3. [`includes/cabecalho.php`](#3-navegação--includescabecalhophp) — menu de categorias do banco
4. [`includes/todas.php`](#4-listagem-compartilhada--includestodasphp) — todas as notícias
5. [`index.php`](#5-home--indexphp) — destaques
6. [`noticia.php`](#6-detalhe--noticiaphp) — notícia completa
7. [`noticias-por-categoria.php`](#7-categoria--noticias-por-categoriaphp) — filtro por categoria
8. [`resultados.php`](#8-busca-back-end--resultadosphp) — endpoint da busca
9. [`js/busca.js`](#9-busca-front-end--jsbuscajs) — busca assíncrona

---

## 1. [Dados] — `src/Services/NoticiaServico.php`
- **Caminho Original (Base/Git):** `src/Services/NoticiaServico.php`
- **Caminho Final (Workspace):** `src/Services/NoticiaServico.php`
- **Finalidade da Alteração:** adicionar as consultas de leitura usadas pela área
  pública (sem sessão/perfil), com `prepared statements`, `LEFT JOIN` do autor,
  enum `Destaque` no lugar de string fixa e desempate determinístico de ordenação.

```diff
--- a/src/Services/NoticiaServico.php
+++ b/src/Services/NoticiaServico.php
@@ -3,6 +3,7 @@ namespace Microblog\Services;
 
 use Exception;
 use Microblog\Database\ConexaoBD;
+use Microblog\Enums\Destaque;
 use Microblog\Enums\TipoUsuario;
 use Microblog\Helpers\Utils;
 use Microblog\Models\Noticia;
@@ -81,6 +82,108 @@ final class NoticiaServico
         }
     }
 
+    /* ------------------------------------------------------------------
+     * Métodos de leitura usados pela área pública (sem sessão/perfil).
+     * ------------------------------------------------------------------ */
+
+    public function listarDestaques(): array
+    {
+        $sql = "SELECT id, titulo, resumo, imagem
+                FROM noticias
+                WHERE destaque = :destaque
+                ORDER BY data DESC, id DESC";
+
+        try {
+            $consulta = $this->conexao->prepare($sql);
+            $consulta->bindValue(":destaque", Destaque::SIM->value);
+            $consulta->execute();
+
+            return $consulta->fetchAll();
+        } catch (PDOException $e) {
+            Utils::registrarLog($e);
+            throw new Exception("Erro ao listar notícias em destaque");
+        }
+    }
+
+    public function listarTodas(): array
+    {
+        $sql = "SELECT id, titulo, data, resumo
+                FROM noticias
+                ORDER BY data DESC, id DESC";
+
+        try {
+            $consulta = $this->conexao->query($sql);
+
+            return $consulta->fetchAll();
+        } catch (PDOException $e) {
+            Utils::registrarLog($e);
+            throw new Exception("Erro ao listar todas as notícias");
+        }
+    }
+
+    public function listarPorCategoria(int $categoriaId): array
+    {
+        $sql = "SELECT noticias.id, noticias.titulo, noticias.data, noticias.resumo,
+                       usuarios.nome AS autor
+                FROM noticias
+                LEFT JOIN usuarios ON noticias.usuario_id = usuarios.id
+                WHERE noticias.categoria_id = :categoria_id
+                ORDER BY noticias.data DESC, noticias.id DESC";
+
+        try {
+            $consulta = $this->conexao->prepare($sql);
+            $consulta->bindValue(":categoria_id", $categoriaId);
+            $consulta->execute();
+
+            return $consulta->fetchAll();
+        } catch (PDOException $e) {
+            Utils::registrarLog($e);
+            throw new Exception("Erro ao listar notícias da categoria");
+        }
+    }
+
+    public function buscarPorIdPublico(int $id): ?array
+    {
+        $sql = "SELECT noticias.id, noticias.data, noticias.titulo, noticias.texto,
+                       noticias.resumo, noticias.imagem, noticias.destaque,
+                       noticias.usuario_id, noticias.categoria_id, usuarios.nome AS autor
+                FROM noticias
+                LEFT JOIN usuarios ON noticias.usuario_id = usuarios.id
+                WHERE noticias.id = :id";
+
+        try {
+            $consulta = $this->conexao->prepare($sql);
+            $consulta->bindValue(":id", $id);
+            $consulta->execute();
+
+            return $consulta->fetch() ?: null;
+        } catch (PDOException $e) {
+            Utils::registrarLog($e);
+            throw new Exception("Erro ao buscar notícia");
+        }
+    }
+
+    public function buscarPorTermo(string $termo): array
+    {
+        $sql = "SELECT id, titulo
+                FROM noticias
+                WHERE titulo LIKE :termo
+                   OR resumo LIKE :termo
+                   OR texto LIKE :termo
+                ORDER BY data DESC, id DESC";
+
+        try {
+            $consulta = $this->conexao->prepare($sql);
+            $consulta->bindValue(":termo", "%" . $termo . "%");
+            $consulta->execute();
+
+            return $consulta->fetchAll();
+        } catch (PDOException $e) {
+            Utils::registrarLog($e);
+            throw new Exception("Erro ao buscar notícias pelo termo informado");
+        }
+    }
+
     public function inserir(Noticia $noticia): void
     {
         $sql = "INSERT INTO noticias (
```

---

## 2. [Utilitários] — `src/Helpers/Utils.php`
- **Caminho Original (Base/Git):** `src/Helpers/Utils.php`
- **Caminho Final (Workspace):** `src/Helpers/Utils.php`
- **Finalidade da Alteração:** dois helpers usados pelas páginas públicas: data em
  ISO 8601 para o atributo `datetime` do `<time>` e a regra de exibição do autor
  ("Equipe Microblog" quando a notícia não tem autor associado).

```diff
--- a/src/Helpers/Utils.php
+++ b/src/Helpers/Utils.php
@@ -88,6 +88,18 @@ class Utils
         return date("d/m/Y H:i", strtotime($data));
     }
 
+    // Formato exigido pelo atributo datetime do elemento <time> (ISO 8601)
+    public static function formataDataIso(string $data): string
+    {
+        return date("Y-m-d\TH:i:s", strtotime($data));
+    }
+
+    // Nome exibido quando a notícia não possui autor associado
+    public static function nomeAutor(?string $autor): string
+    {
+        return ($autor ?? "") !== "" ? $autor : "Equipe Microblog";
+    }
+
     public static function codificarSenha(string $senha): string
     {
         return password_hash($senha, PASSWORD_DEFAULT);
```

---

## 3. [Navegação] — `includes/cabecalho.php`
- **Caminho Original (Base/Git):** `includes/cabecalho.php`
- **Caminho Final (Workspace):** `includes/cabecalho.php`
- **Finalidade da Alteração:** substituir os itens fixos do menu de categorias por
  itens vindos do banco (`CategoriaServico::listarTodos()`), com autoload no
  próprio include e `try/catch` para que falha de conexão não derrube o site.

```diff
--- a/includes/cabecalho.php
+++ b/includes/cabecalho.php
@@ -1,3 +1,22 @@
+<?php
+
+require_once __DIR__ . '/../vendor/autoload.php';
+
+use Microblog\Helpers\Utils;
+use Microblog\Services\CategoriaServico;
+
+/* O menu de categorias é alimentado pelo banco de dados.
+Uma falha de conexão não pode impedir a exibição da página:
+registramos o erro em log e o menu fica apenas vazio. */
+$categoriasMenu = [];
+
+try {
+  $categoriaServico = new CategoriaServico();
+  $categoriasMenu = $categoriaServico->listarTodos();
+} catch (Throwable $e) {
+  Utils::registrarLog($e);
+}
+?>
 <!DOCTYPE html>
 <html lang="pt-br" class="h-100">
 
@@ -28,10 +47,11 @@
             <li class="nav-item dropdown">
               <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Categorias</a>
               <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
-
-                <li><a class="dropdown-item" href="noticias-por-categoria.php?id=1">Desenvolvimento</a></li>
-                <li><a class="dropdown-item" href="noticias-por-categoria.php?id=2">Tecnologia</a></li>
-                
+                <?php foreach ($categoriasMenu as $categoriaMenu): ?>
+                  <li>
+                    <a class="dropdown-item" href="noticias-por-categoria.php?id=<?= Utils::escaparHtml($categoriaMenu['id']) ?>"><?= Utils::escaparHtml($categoriaMenu['nome']) ?></a>
+                  </li>
+                <?php endforeach; ?>
               </ul>
             </li>
             <li class="nav-item"><a class="nav-link" href="admin/index.php"><i class="bi bi-lock-fill"></i> Área administrativa</a></li>
```

---

## 4. [Listagem compartilhada] — `includes/todas.php`
- **Caminho Original (Base/Git):** `includes/todas.php`
- **Caminho Final (Workspace):** `includes/todas.php`
- **Finalidade da Alteração:** listar todas as notícias do banco
  (`NoticiaServico::listarTodas()`), com data formatada, `datetime` ISO, escape de
  saída e alerta de erro isolado (o include é usado por várias páginas).

```diff
--- a/includes/todas.php
+++ b/includes/todas.php
@@ -1,3 +1,21 @@
+<?php
+
+require_once __DIR__ . '/../vendor/autoload.php';
+
+use Microblog\Helpers\Utils;
+use Microblog\Services\NoticiaServico;
+
+$todasAsNoticias = [];
+$mensagemErroTodas = "";
+
+try {
+    $servicoTodas = new NoticiaServico();
+    $todasAsNoticias = $servicoTodas->listarTodas();
+} catch (Throwable $e) {
+    Utils::registrarLog($e);
+    $mensagemErroTodas = "Não foi possível carregar a lista de notícias";
+}
+?>
 <hr class="my-5 w-50 mx-auto">
 
 <div class="row my-1">
@@ -5,14 +23,19 @@
         <div class="list-group">
             <h2 class="fs-6 text-center text-muted">Todas as notícias</h2>
 
-            <a href="noticia.php?id=1" class="list-group-item list-group-item-action">
-                <h3 class="fs-6"><time datetime="2026-01-01">01/01/2026</time> - Título provisório da notícia</h3>
-                <p>Resumo provisório para apresentar o formato da lista.</p>
-            </a>
-            <a href="noticia.php?id=2" class="list-group-item list-group-item-action">
-                <h3 class="fs-6"><time datetime="2026-01-02">02/01/2026</time> - Outro título provisório</h3>
-                <p>Outro resumo provisório para apresentar o formato da lista.</p>
-            </a>
+            <?php if ($mensagemErroTodas !== ""): ?>
+                <p class="alert alert-danger text-center mb-0"><?= Utils::escaparHtml($mensagemErroTodas) ?></p>
+            <?php endif; ?>
+
+            <?php foreach ($todasAsNoticias as $noticia): ?>
+                <a href="noticia.php?id=<?= Utils::escaparHtml($noticia['id']) ?>" class="list-group-item list-group-item-action">
+                    <h3 class="fs-6">
+                        <time datetime="<?= Utils::escaparHtml(Utils::formataDataIso($noticia['data'])) ?>"><?= Utils::escaparHtml(Utils::formataData($noticia['data'])) ?></time>
+                        - <?= Utils::escaparHtml($noticia['titulo']) ?>
+                    </h3>
+                    <p><?= Utils::escaparHtml($noticia['resumo']) ?></p>
+                </a>
+            <?php endforeach; ?>
         </div>
     </div>
 </div>
```

---

## 5. [Home] — `index.php`
- **Caminho Original (Base/Git):** `index.php`
- **Caminho Final (Workspace):** `index.php`
- **Finalidade da Alteração:** trocar os dois cards provisórios pelos destaques do
  banco, mantendo a estrutura do card e o link dinâmico para `noticia.php`.

```diff
--- a/index.php
+++ b/index.php
@@ -1,35 +1,49 @@
-<?php require_once __DIR__ . '/includes/cabecalho.php'; ?>
+<?php
+
+use Microblog\Helpers\Utils;
+use Microblog\Services\NoticiaServico;
+
+require_once __DIR__ . '/vendor/autoload.php';
+
+$noticiasEmDestaque = [];
+$mensagemErro = "";
+
+try {
+    $noticiaServico = new NoticiaServico();
+    $noticiasEmDestaque = $noticiaServico->listarDestaques();
+} catch (Throwable $e) {
+    Utils::registrarLog($e);
+    $mensagemErro = "Não foi possível carregar as notícias em destaque";
+}
+
+require_once __DIR__ . '/includes/cabecalho.php';
+?>
 
 <div class="row my-1 mx-md-n1">
 
-    <!-- Conteúdo estático provisório: depois será substituído pelos destaques do banco. -->
-    <div class="col-md-6 my-1 px-md-1">
-        <article class="card shadow-sm h-100">
-            <a href="noticia.php?id=1" class="card-link">
-                <img src="images/abstrato.jpg" class="card-img-top" alt="">
-                <div class="card-body">
-                    <h2 class="fs-4 card-title">Título de uma notícia em destaque</h2>
-                    <p class="card-text">Um resumo provisório para apresentar o visual do cartão.</p>
-                </div>
-            </a>
-        </article>
-    </div>
-
-    <div class="col-md-6 my-1 px-md-1">
-        <article class="card shadow-sm h-100">
-            <a href="noticia.php?id=2" class="card-link">
-                <img src="images/cores.jpg" class="card-img-top" alt="">
-                <div class="card-body">
-                    <h2 class="fs-4 card-title">Outro título de notícia em destaque</h2>
-                    <p class="card-text">Outro resumo provisório para compor a página inicial.</p>
-                </div>
-            </a>
-        </article>
-    </div>
-    
+    <?php if ($mensagemErro !== ""): ?>
+        <div class="col-12 px-md-1">
+            <p class="alert alert-danger text-center mb-0"><?= Utils::escaparHtml($mensagemErro) ?></p>
+        </div>
+    <?php endif; ?>
+
+    <?php foreach ($noticiasEmDestaque as $noticiaDestaque): ?>
+        <div class="col-md-6 my-1 px-md-1">
+            <article class="card shadow-sm h-100">
+                <a href="noticia.php?id=<?= Utils::escaparHtml($noticiaDestaque['id']) ?>" class="card-link">
+                    <img src="images/<?= Utils::escaparHtml($noticiaDestaque['imagem']) ?>" class="card-img-top" alt="">
+                    <div class="card-body">
+                        <h2 class="fs-4 card-title"><?= Utils::escaparHtml($noticiaDestaque['titulo']) ?></h2>
+                        <p class="card-text"><?= Utils::escaparHtml($noticiaDestaque['resumo']) ?></p>
+                    </div>
+                </a>
+            </article>
+        </div>
+    <?php endforeach; ?>
+
 </div>
 
 <?php
 require_once __DIR__ . '/includes/todas.php';
 require_once __DIR__ . '/includes/rodape.php';
 ?>
```

---

## 6. [Detalhe] — `noticia.php`
- **Caminho Original (Base/Git):** `noticia.php`
- **Caminho Final (Workspace):** `noticia.php`
- **Finalidade da Alteração:** carregar a notícia pelo `id` da URL, com validação de
  id, caso "não encontrada" e exibição de data/autor/imagem/texto do banco.

```diff
--- a/noticia.php
+++ b/noticia.php
@@ -1,20 +1,47 @@
-<?php require_once __DIR__ . '/includes/cabecalho.php'; ?>
+<?php
+
+use Microblog\Helpers\Utils;
+use Microblog\Services\NoticiaServico;
+
+require_once __DIR__ . '/vendor/autoload.php';
+
+// Garante que o ID recebido pela URL é um inteiro positivo
+Utils::verificarId($_GET['id'] ?? null);
+$idNoticia = (int) $_GET['id'];
+
+$noticia = null;
+
+try {
+    $noticiaServico = new NoticiaServico();
+    $noticia = $noticiaServico->buscarPorIdPublico($idNoticia);
+} catch (Throwable $e) {
+    Utils::registrarLog($e);
+    Utils::alertaErro("Não foi possível carregar a notícia.");
+}
+
+// ID válido, mas sem notícia correspondente no banco
+if ($noticia === null) {
+    Utils::alertaErro("Notícia não encontrada.");
+}
+
+require_once __DIR__ . '/includes/cabecalho.php';
+?>
 
 <div class="row my-1 mx-md-n1">
 
     <article class="col-12">
-        <h2>Título provisório da notícia</h2>
+        <h2><?= Utils::escaparHtml($noticia['titulo']) ?></h2>
         <p class="font-weight-light">
-            <time datetime="2026-01-01">01/01/2026</time> -
-            <span>Nome do autor</span>
+            <time datetime="<?= Utils::escaparHtml(Utils::formataDataIso($noticia['data'])) ?>"><?= Utils::escaparHtml(Utils::formataData($noticia['data'])) ?></time> -
+            <span><?= Utils::escaparHtml(Utils::nomeAutor($noticia['autor'])) ?></span>
         </p>
-        <img src="images/conversa.jpg" alt="" class="float-start pe-2 img-fluid">
-        <p class="ajusta-texto">Este é um texto provisório. Mais tarde, o conteúdo será carregado dinamicamente a partir do banco de dados.</p>
+        <img src="images/<?= Utils::escaparHtml($noticia['imagem']) ?>" alt="" class="float-start pe-2 img-fluid">
+        <p class="ajusta-texto"><?= Utils::escaparHtml($noticia['texto']) ?></p>
     </article>
-
+
 </div>
 
 <?php
 require_once __DIR__ . '/includes/todas.php';
 require_once __DIR__ . '/includes/rodape.php';
 ?>
```

---

## 7. [Categoria] — `noticias-por-categoria.php`
- **Caminho Original (Base/Git):** `noticias-por-categoria.php`
- **Caminho Final (Workspace):** `noticias-por-categoria.php`
- **Finalidade da Alteração:** filtrar as notícias pela categoria escolhida no menu,
  com o nome da categoria vindo do banco, fallback de autor e o caso "sem notícias".

```diff
--- a/noticias-por-categoria.php
+++ b/noticias-por-categoria.php
@@ -1,36 +1,69 @@
-<?php require_once __DIR__ . '/includes/cabecalho.php'; ?>
+<?php
+
+use Microblog\Helpers\Utils;
+use Microblog\Services\CategoriaServico;
+use Microblog\Services\NoticiaServico;
+
+require_once __DIR__ . '/vendor/autoload.php';
+
+// Garante que o ID recebido pela URL é um inteiro positivo
+Utils::verificarId($_GET['id'] ?? null);
+$categoriaId = (int) $_GET['id'];
+
+$categoria = null;
+$noticiasDaCategoria = [];
+$mensagemErro = "";
+
+try {
+    $categoriaServico = new CategoriaServico();
+    $categoria = $categoriaServico->buscarPorId($categoriaId);
+
+    $noticiaServico = new NoticiaServico();
+    $noticiasDaCategoria = $noticiaServico->listarPorCategoria($categoriaId);
+} catch (Throwable $e) {
+    Utils::registrarLog($e);
+    $mensagemErro = "Não foi possível carregar as notícias da categoria";
+}
+
+require_once __DIR__ . '/includes/cabecalho.php';
+?>
 
 <div class="row my-1 mx-md-n1">
 
     <article class="col-12">
-    
-        <h2 class="text-center">
-            Notícias sobre <span class="badge bg-primary">Desenvolvimento</span>
-        </h2>
+
+        <?php if ($mensagemErro !== ""): ?>
+            <h2 class="alert alert-danger text-center"><?= Utils::escaparHtml($mensagemErro) ?></h2>
+        <?php elseif ($categoria === null || $noticiasDaCategoria === []): ?>
+            <h2 class="alert alert-warning text-center">Não há notícias desta categoria</h2>
+        <?php else: ?>
+            <h2 class="text-center">
+                Notícias sobre <span class="badge bg-primary"><?= Utils::escaparHtml($categoria['nome']) ?></span>
+            </h2>
+        <?php endif; ?>
 
         <div class="row my-1">
             <div class="col-12 px-md-1">
                 <div class="list-group">
-
-                    <a href="noticia.php?id=1" class="list-group-item list-group-item-action">
-                        <h3 class="fs-6">Título provisório da notícia</h3>
-                        <p><time datetime="2026-01-01">01/01/2026</time> - Nome do autor</p>
-                        <p>Resumo provisório da notícia desta categoria.</p>
-                    </a>
-                    <a href="noticia.php?id=2" class="list-group-item list-group-item-action">
-                        <h3 class="fs-6">Outro título provisório</h3>
-                        <p><time datetime="2026-01-02">02/01/2026</time> - Nome do autor</p>
-                        <p>Outro resumo provisório para demonstrar a listagem.</p>
-                    </a>
-
+                    <?php foreach ($noticiasDaCategoria as $noticia): ?>
+                        <a href="noticia.php?id=<?= Utils::escaparHtml($noticia['id']) ?>" class="list-group-item list-group-item-action">
+                            <h3 class="fs-6"><?= Utils::escaparHtml($noticia['titulo']) ?></h3>
+                            <p>
+                                <time datetime="<?= Utils::escaparHtml(Utils::formataDataIso($noticia['data'])) ?>"><?= Utils::escaparHtml(Utils::formataData($noticia['data'])) ?></time>
+                                - <?= Utils::escaparHtml(Utils::nomeAutor($noticia['autor'])) ?>
+                            </p>
+                            <p><?= Utils::escaparHtml($noticia['resumo']) ?></p>
+                        </a>
+                    <?php endforeach; ?>
                 </div>
             </div>
         </div>
-        
+
     </article>
+
 </div>
 
 <?php
 require_once __DIR__ . '/includes/todas.php';
 require_once __DIR__ . '/includes/rodape.php';
 ?>
```

---

## 8. [Busca (back-end)] — `resultados.php`
- **Caminho Original (Base/Git):** `resultados.php`
- **Caminho Final (Workspace):** `resultados.php`
- **Finalidade da Alteração:** substituir o HTML provisório pelo endpoint real da
  busca: termo normalizado e limitado, consulta parametrizada e fragmento HTML com
  os três estados (resultados, sem notícias, erro).

```diff
--- a/resultados.php
+++ b/resultados.php
@@ -1,12 +1,38 @@
-<!-- Resposta HTML provisória usada pela busca enquanto o back-end não é implementado. -->
-<h2 class="fs-5">Resultados: <span class="badge rounded-pill text-bg-success">2</span></h2>
-<div class="list-group">
-
-    <a class="list-group-item list-group-item-action" href="noticia.php?id=1">
-        Título provisório da notícia
-    </a>
-    <a class="list-group-item list-group-item-action" href="noticia.php?id=2">
-        Outro título provisório
-    </a>
-    
-</div>
+<?php
+
+use Microblog\Helpers\Utils;
+use Microblog\Services\NoticiaServico;
+
+require_once __DIR__ . '/vendor/autoload.php';
+
+/* Fragmento HTML consumido por js/busca.js através de fetch (POST).
+A página não possui layout próprio: devolve apenas a lista de resultados. */
+
+// Termo digitado no campo de busca do cabeçalho
+$termo = mb_substr(Utils::normalizarEntrada($_POST['busca'] ?? ''), 0, 100);
+
+$noticiasEncontradas = [];
+$ocorreuErro = false;
+
+if ($termo !== "") {
+    try {
+        $noticiaServico = new NoticiaServico();
+        $noticiasEncontradas = $noticiaServico->buscarPorTermo($termo);
+    } catch (Throwable $e) {
+        Utils::registrarLog($e);
+        $ocorreuErro = true;
+    }
+}
+
+if ($ocorreuErro): ?>
+    <p class="alert alert-warning mb-0">Não foi possível realizar a busca agora.</p>
+<?php elseif ($noticiasEncontradas === []): ?>
+    <h2 class="fs-5 text-danger">Sem notícias</h2>
+<?php else: ?>
+    <h2 class="fs-5">Resultados: <span class="badge rounded-pill text-bg-success"><?= count($noticiasEncontradas) ?></span></h2>
+    <div class="list-group">
+        <?php foreach ($noticiasEncontradas as $noticia): ?>
+            <a class="list-group-item list-group-item-action" href="noticia.php?id=<?= Utils::escaparHtml($noticia['id']) ?>"><?= Utils::escaparHtml($noticia['titulo']) ?></a>
+        <?php endforeach; ?>
+    </div>
+<?php endif; ?>
```

---

## 9. [Busca (front-end)] — `js/busca.js`
- **Caminho Original (Base/Git):** `js/busca.js`
- **Caminho Final (Workspace):** `js/busca.js`
- **Finalidade da Alteração:** implementar a busca viva do cabeçalho: `input` com
  debounce de 300 ms, `FormData` + `fetch` POST, controle de resposta obsoleta,
  `aria-busy` e mensagem de erro dentro da própria área de resultados.

```diff
--- a/js/busca.js
+++ b/js/busca.js
@@ -1,2 +1,77 @@
 'use strict';
 
+// Seleciona os elementos da busca pelo id
+const formBusca = document.querySelector("#form-busca");
+const campoBusca = document.querySelector("#campo-busca");
+const divResultados = document.querySelector("#resultados");
+
+let temporizadorBusca;
+// Identifica a consulta mais recente para descartar respostas antigas
+let buscaAtual = 0;
+
+// Enquanto o usuário digita, atualiza os resultados após uma pausa
+campoBusca.addEventListener("input", function () {
+    clearTimeout(temporizadorBusca);
+
+    // Campo vazio (ou apenas com espaços): limpa e esconde os resultados
+    if (campoBusca.value.trim() === "") {
+        buscaAtual++;
+        esconderResultados();
+        return;
+    }
+
+    // Reinicia a espera a cada tecla: a consulta só começa após 300 ms
+    // sem digitação. O número também invalida uma resposta pendente.
+    const numeroBusca = ++buscaAtual;
+    temporizadorBusca = setTimeout(() => realizarBusca(numeroBusca), 300);
+});
+
+function esconderResultados() {
+    divResultados.setAttribute("hidden", "");
+    divResultados.innerHTML = "";
+    divResultados.setAttribute("aria-busy", "false");
+}
+
+async function realizarBusca(numeroBusca) {
+    divResultados.removeAttribute("hidden");
+    // Informa às tecnologias assistivas que a região está sendo atualizada
+    divResultados.setAttribute("aria-busy", "true");
+
+    try {
+        // Envia o campo do formulário para o PHP
+        const resposta = await fetch("resultados.php", {
+            method: "POST",
+            body: new FormData(formBusca)
+        });
+
+        if (!resposta.ok) {
+            throw new Error("A busca retornou uma resposta inválida.");
+        }
+
+        // resultados.php devolve HTML com os valores já escapados
+        const dados = await resposta.text();
+
+        // Uma consulta mais nova começou enquanto esta aguardava a resposta
+        if (numeroBusca !== buscaAtual) {
+            return;
+        }
+
+        divResultados.innerHTML = dados;
+    } catch (error) {
+        if (numeroBusca !== buscaAtual) {
+            return;
+        }
+
+        console.error("Falha ao buscar notícias.", error);
+
+        const mensagemErro = document.createElement("p");
+        mensagemErro.className = "alert alert-warning mb-0";
+        mensagemErro.textContent = "Não foi possível realizar a busca agora.";
+        divResultados.replaceChildren(mensagemErro);
+    } finally {
+        // Só encerra o estado ocupado da consulta mais recente
+        if (numeroBusca === buscaAtual) {
+            divResultados.setAttribute("aria-busy", "false");
+        }
+    }
+}
```
