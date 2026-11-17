<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Microblog\Helpers\Utils;
use Microblog\Services\NoticiaServico;

$todasAsNoticias = [];
$mensagemErroTodas = "";

try {
    $servicoTodas = new NoticiaServico();
    $todasAsNoticias = $servicoTodas->listarTodas();
} catch (Throwable $e) {
    Utils::registrarLog($e);
    $mensagemErroTodas = "Não foi possível carregar a lista de notícias";
}
?>
<hr class="my-5 w-50 mx-auto">

<div class="row my-1">
    <div class="col-12 px-md-1">
        <div class="list-group">
            <h2 class="fs-6 text-center text-muted">Todas as notícias</h2>

            <?php if ($mensagemErroTodas !== ""): ?>
                <p class="alert alert-danger text-center mb-0"><?= Utils::escaparHtml($mensagemErroTodas) ?></p>
            <?php endif; ?>

            <?php foreach ($todasAsNoticias as $noticia): ?>
                <a href="noticia.php?id=<?= Utils::escaparHtml($noticia['id']) ?>" class="list-group-item list-group-item-action">
                    <h3 class="fs-6">
                        <time datetime="<?= Utils::escaparHtml(Utils::formataDataIso($noticia['data'])) ?>"><?= Utils::escaparHtml(Utils::formataData($noticia['data'])) ?></time>
                        - <?= Utils::escaparHtml($noticia['titulo']) ?>
                    </h3>
                    <p><?= Utils::escaparHtml($noticia['resumo']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
