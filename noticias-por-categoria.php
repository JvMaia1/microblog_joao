<?php

use Microblog\Helpers\Utils;
use Microblog\Services\CategoriaServico;
use Microblog\Services\NoticiaServico;

require_once __DIR__ . '/vendor/autoload.php';

// Garante que o ID recebido pela URL é um inteiro positivo
Utils::verificarId($_GET['id'] ?? null);
$categoriaId = (int) $_GET['id'];

$categoria = null;
$noticiasDaCategoria = [];
$mensagemErro = "";

try {
    $categoriaServico = new CategoriaServico();
    $categoria = $categoriaServico->buscarPorId($categoriaId);

    $noticiaServico = new NoticiaServico();
    $noticiasDaCategoria = $noticiaServico->listarPorCategoria($categoriaId);
} catch (Throwable $e) {
    Utils::registrarLog($e);
    $mensagemErro = "Não foi possível carregar as notícias da categoria";
}

require_once __DIR__ . '/includes/cabecalho.php';
?>

<div class="row my-1 mx-md-n1">

    <article class="col-12">

        <?php if ($mensagemErro !== ""): ?>
            <h2 class="alert alert-danger text-center"><?= Utils::escaparHtml($mensagemErro) ?></h2>
        <?php elseif ($categoria === null || $noticiasDaCategoria === []): ?>
            <h2 class="alert alert-warning text-center">Não há notícias desta categoria</h2>
        <?php else: ?>
            <h2 class="text-center">
                Notícias sobre <span class="badge bg-primary"><?= Utils::escaparHtml($categoria['nome']) ?></span>
            </h2>
        <?php endif; ?>

        <div class="row my-1">
            <div class="col-12 px-md-1">
                <div class="list-group">
                    <?php foreach ($noticiasDaCategoria as $noticia): ?>
                        <a href="noticia.php?id=<?= Utils::escaparHtml($noticia['id']) ?>" class="list-group-item list-group-item-action">
                            <h3 class="fs-6"><?= Utils::escaparHtml($noticia['titulo']) ?></h3>
                            <p>
                                <time datetime="<?= Utils::escaparHtml(Utils::formataDataIso($noticia['data'])) ?>"><?= Utils::escaparHtml(Utils::formataData($noticia['data'])) ?></time>
                                - <?= Utils::escaparHtml(Utils::nomeAutor($noticia['autor'])) ?>
                            </p>
                            <p><?= Utils::escaparHtml($noticia['resumo']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </article>

</div>

<?php
require_once __DIR__ . '/includes/todas.php';
require_once __DIR__ . '/includes/rodape.php';
?>