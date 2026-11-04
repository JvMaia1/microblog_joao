<?php
namespace Microblog\Services;

use Exception;
use Microblog\Database\ConexaoBD;
use Microblog\Helpers\Utils;
use PDO;
use PDOException;

final class UsuarioServico
{
    private PDO $conexao;

    /* Ao criar um objeto UsuarioServico,
    o construtor fica responsável por chamar/executar
    a conexão com o banco de dados 
    (proveniente do método estático getConexao). 
    Os dados da conexão ficarão guardados na propriedade conexao. */
    public function __construct()
    {
        $this->conexao = ConexaoBD::getConexao();
    }

    public function listarTodos():array
    {
        $sql = "SELECT id, nome, email, nivel_de_acesso FROM usuarios ORDER BY nome";

        try {
            $consulta = $this->conexao->query($sql);
            return $consulta->fetchAll();
        } catch (PDOException $e) {
            // Registrar/logar os detalhes do erro em logs/erro.log
            Utils::registrarLog($e);

            // Lançando uma mensagem de erro amigável (sem detalhes)
            // Essa mensagem será posteriormente capturada e exibida no Front-End
            throw new Exception("Erro ao listar usuários", 0, $e);
        }
    }

    public function inserir(Usuario $usuario): void {
        $sql = "INSERT into usuarios (nome, email, senha, nivel_de_acesso) VALUES (:nome, :email, :senha, :nivel_de_acesso)";

        try{
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(':nome', $usuario->getNome());
            $consulta->bindValue(':email', $usuario->getEmail());
            $consulta->bindValue(':senha', $usuario->getSenha());
            $consulta->bindValue(':nivel_de_acesso', $usuario->getNivelDeAcesso());
            $consulta->execute();
        } catch(PDOException $e) {
            
        }
        
    }
}