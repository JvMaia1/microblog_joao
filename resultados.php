<?php

use Microblog\Helpers\Utils;
use Microblog\Services\NoticiaServico;

require_once __DIR__ . '/vendor/autoload.php';

/* Fragmento HTML consumido por js/busca.js através de fetch (POST).
A página não possui layout próprio: devolve apenas a lista de resultados. */

// Termo digitado no campo de busca do cabeçalho
$termo = mb_substr(Utils::normalizarEntrada($_POST['busca'] ?? ''), 0, 100);

$noticiasEncontradas = [];
$ocorreuErro = false;

if ($termo !== "") {
    try {
        $noticiaServico = new NoticiaServico();
        $noticiasEncontradas = $noticiaServico->buscarPorTermo($termo);
    } catch (Throwable $e) {
        Utils::registrarLog($e);
        $ocorreuErro = true;
    }
}

if ($ocorreuErro): ?>
    <p class="alert alert-warning mb-0">Não foi possível realizar a busca agora.</p>
<?php elseif ($noticiasEncontradas === []): ?>
    <h2 class="fs-5 text-danger">Sem notícias</h2>
<?php else: ?>
    <h2 class="fs-5">Resultados: <span class="badge rounded-pill text-bg-success"><?= count($noticiasEncontradas) ?></span></h2>
    <div class="list-group">
        <?php foreach ($noticiasEncontradas as $noticia): ?>
            <a class="list-group-item list-group-item-action" href="noticia.php?id=<?= Utils::escaparHtml($noticia['id']) ?>"><?= Utils::escaparHtml($noticia['titulo']) ?></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>