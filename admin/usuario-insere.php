<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Microblog\Services\UsuarioServico;
use Microblog\Utils;
use Microblog\Validacoes;
use Microblog\TipoEnum;
use Microblog\Usuario;


$mensagem = '';
$nome = '';
$email = '';
$nivel_de_acesso = '';
$senhaBruta = '';

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $nome = Utils::normalizarEntrada($_POST['nome'] ?? "");
    $email = Utils::normalizarEntrada($_POST['email'] ?? "", 'email');
    $nivel_de_acesso = Utils::normalizarEntrada($_POST['nivel_de_acesso'] ?? "");
    $senhaBruta = $_POST['senha'];

    Validacoes::validarNome($nome);
    Validacoes::validarEmail($email);
    Validacoes::validarNivelDeAcesso($nivel_de_acesso);
    Validacoes::validarSenha($senhaBruta);

    $senha = Utils::codificarSenha($senhaBruta);

    $tipoEnum = TipoEnum::from($nivel_de_acesso);

    $usuario = new Usuario($nome, $email, $tipoEnum, $senha, );

    $UsuarioServico = new UsuarioServico();

    $usuarioServico->inserir($usuario);

    header("location:usuarios.php");
    exit;
}

require_once __DIR__ . '/../includes/cabecalho-admin.php'; 
?>

<div class="row">
    <article class="col-12 bg-white rounded shadow my-1 py-4">
        <h2 class="text-center">Inserir novo usuário</h2>
        <form class="mx-auto w-75" action="" method="post" id="form-inserir" name="form-inserir">
            <div class="mb-3">
                <label class="form-label" for="nome">Nome:</label>
                <input class="form-control" type="text" id="nome" name="nome" value="Nome de exemplo">
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail:</label>
                <input class="form-control" type="email" id="email" name="email" value="pessoa@exemplo.com">
            </div>
            <div class="mb-3">
                <label class="form-label" for="nivel_de_acesso">Nível de acesso:</label>
                <input class="form-control" type="text" id="nivel_de_acesso" name="nivel_de_acesso" value="editor">
            </div>
            <div class="mb-3">
                <label class="form-label" for="tipo">Tipo:</label>
                <select class="form-select" name="tipo" id="tipo">
                    <option value="editor" selected>Editor</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <button class="btn btn-primary" id="inserir" name="inserir"><i class="bi bi-save"></i> Inserir</button>
        </form>
    </article>
</div>

<?php require_once __DIR__ . '/../includes/rodape-admin.php'; ?>
