<?php
require_once __DIR__ . '/../includes/cabecalho-admin.php'; 
require_once __DIR__ . '/../vendor/autoload.php';

use Microblog\Auth\ControleDeAcesso;
use Microblog\Enums\TipoUsuario;
use Microblog\Helpers\Utils;
use Microblog\Helpers\Validacoes;
use Microblog\Models\Usuario;
use Microblog\Services\UsuarioServico;

$mensagemErro = "";
$dadosUsuario = null;
$usuarioServico = null;
$id = Utils::normalizarEntrada($_SESSION['id'] ?? null, 'inteiro');
// $id = $_SESSION['id'];

try {
    $usuarioServico = new UsuarioServico();
    $dadosUsuario = $usuarioServico->buscarPorId($id);
} catch(Throwable $e){
    Utils::registrarLog($e);
    $mensagemErro = "Não foi possível carregar os dados do meu perfil";
}

if($dadosUsuario === null && $mensagemErro === ""){
    Utils::alertaErro("Usuário não encontrado!");
}

$nome = $dadosUsuario['nome'] ?? "";
$email = $dadosUsuario['email'] ?? "";
?>

<div class="row">
    <article class="col-12 bg-white rounded shadow my-1 py-4">
        <h2 class="text-center">Atualizar meus dados</h2>
        <form class="mx-auto w-75" action="" method="post" id="form-atualizar" name="form-atualizar">
            <div class="mb-3">
                <label class="form-label" for="nome">Nome:</label>
                <input class="form-control" type="text" id="nome" name="nome" value="<?= $nome ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail:</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= $email ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="senha">Senha:</label>
                <input class="form-control" type="password" id="senha" name="senha" placeholder="Preencha apenas se for alterar">
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
        </form>
    </article>
</div>

<?php require_once __DIR__ . '/../includes/rodape-admin.php'; ?>
