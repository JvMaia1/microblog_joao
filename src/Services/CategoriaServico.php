<?php
use Microblog\Database\ConexaoBD;
use Microblog\Helpers\Utils;

final class CategoriaServico
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBD::getConexao();
    }

    public function listarTodos():array
    {
        $sql = "SELECT id, nome FROM categorias ORDER BY nome";

        try {
            $consulta = $this->conexao->query($sql);
            return $consulta->fetchAll();
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao listar categorias");
        }
    }
}