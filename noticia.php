<?php

use Microblog\Helpers\Utils;
use Microblog\Services\NoticiaServico;

require_once __DIR__ . '/vendor/autoload.php';

// Garante que o ID recebido pela URL é um inteiro positivo
Utils::verificarId($_GET['id'] ?? null);
$idNoticia = (int) $_GET['id'];

$noticia = null;

try {
    $noticiaServico = new NoticiaServico();
    $noticia = $noticiaServico->buscarPorIdPublico($idNoticia);
} catch (Throwable $e) {
    Utils::registrarLog($e);
    Utils::alertaErro("Não foi possível carregar a notícia.");
}

// ID válido, mas sem notícia correspondente no banco
if ($noticia === null) {
    Utils::alertaErro("Notícia não encontrada.");
}

require_once __DIR__ . '/includes/cabecalho.php';
?>

<div class="row my-1 mx-md-n1">

    <article class="col-12">
        <h2><?= Utils::escaparHtml($noticia['titulo']) ?></h2>
        <p class="font-weight-light">
            <time datetime="<?= Utils::escaparHtml(Utils::formataDataIso($noticia['data'])) ?>"><?= Utils::escaparHtml(Utils::formataData($noticia['data'])) ?></time> -
            <span><?= Utils::escaparHtml(Utils::nomeAutor($noticia['autor'])) ?></span>
        </p>
        <img src="images/<?= Utils::escaparHtml($noticia['imagem']) ?>" alt="" class="float-start pe-2 img-fluid">
        <p class="ajusta-texto"><?= Utils::escaparHtml($noticia['texto']) ?></p>
    </article>

</div>

<?php
require_once __DIR__ . '/includes/todas.php';
require_once __DIR__ . '/includes/rodape.php';
?>