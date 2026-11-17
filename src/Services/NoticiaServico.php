<?php
namespace Microblog\Services;

use Exception;
use Microblog\Database\ConexaoBD;
use Microblog\Enums\Destaque;
use Microblog\Enums\TipoUsuario;
use Microblog\Helpers\Utils;
use Microblog\Models\Noticia;
use PDO;
use PDOException;

final class NoticiaServico
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBD::getConexao();
    }

    public function listarTodos(TipoUsuario $tipoUsuario, int $usuarioId): array
    {
        if ($tipoUsuario === TipoUsuario::ADMIN) {
            $sql = "SELECT noticias.id, noticias.titulo, noticias.data, noticias.destaque,
                           usuarios.nome AS autor
                    FROM noticias
                    LEFT JOIN usuarios ON noticias.usuario_id = usuarios.id
                    ORDER BY noticias.data DESC";

            try {
                $consulta = $this->conexao->query($sql);
                return $consulta->fetchAll();
            } catch (PDOException $e) {
                Utils::registrarLog($e);
                throw new Exception("Erro ao listar notícias");
            }
        }

        $sql = "SELECT id, titulo, data, destaque
                FROM noticias
                WHERE usuario_id = :usuario_id
                ORDER BY data DESC";

        try {
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(":usuario_id", $usuarioId);
            $consulta->execute();
            return $consulta->fetchAll();
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao listar notícias");
        }
    }

    public function buscarPorId(int $id, TipoUsuario $tipoUsuario, int $usuarioId): ?array
    {
        $sql = "SELECT noticias.id, noticias.data, noticias.titulo, noticias.texto,
                       noticias.resumo, noticias.imagem, noticias.destaque,
                       noticias.usuario_id, noticias.categoria_id, usuarios.nome AS autor
                FROM noticias
                LEFT JOIN usuarios ON noticias.usuario_id = usuarios.id
                WHERE noticias.id = :id";

        if ($tipoUsuario === TipoUsuario::EDITOR) {
            $sql .= " AND noticias.usuario_id = :usuario_id";
        }

        try {
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(":id", $id);

            if ($tipoUsuario === TipoUsuario::EDITOR) {
                $consulta->bindValue(":usuario_id", $usuarioId);
            }

            $consulta->execute();
            return $consulta->fetch() ?: null;
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao buscar notícia por ID.");
        }
    }

    /* ------------------------------------------------------------------
     * Métodos de leitura usados pela área pública (sem sessão/perfil).
     * ------------------------------------------------------------------ */

    public function listarDestaques(): array
    {
        $sql = "SELECT id, titulo, resumo, imagem
                FROM noticias
                WHERE destaque = :destaque
                ORDER BY data DESC";

        try {
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(":destaque", Destaque::SIM->value);
            $consulta->execute();

            return $consulta->fetchAll();
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao listar notícias em destaque");
        }
    }

    public function listarTodas(): array
    {
        $sql = "SELECT id, titulo, data, resumo
                FROM noticias
                ORDER BY data DESC";

        try {
            $consulta = $this->conexao->query($sql);

            return $consulta->fetchAll();
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao listar todas as notícias");
        }
    }

    public function listarPorCategoria(int $categoriaId): array
    {
        $sql = "SELECT noticias.id, noticias.titulo, noticias.data, noticias.resumo,
                       usuarios.nome AS autor
                FROM noticias
                LEFT JOIN usuarios ON noticias.usuario_id = usuarios.id
                WHERE noticias.categoria_id = :categoria_id
                ORDER BY noticias.data DESC";

        try {
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(":categoria_id", $categoriaId);
            $consulta->execute();

            return $consulta->fetchAll();
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao listar notícias da categoria");
        }
    }

    public function buscarPorIdPublico(int $id): ?array
    {
        $sql = "SELECT noticias.id, noticias.data, noticias.titulo, noticias.texto,
                       noticias.resumo, noticias.imagem, noticias.destaque,
                       noticias.usuario_id, noticias.categoria_id, usuarios.nome AS autor
                FROM noticias
                LEFT JOIN usuarios ON noticias.usuario_id = usuarios.id
                WHERE noticias.id = :id";

        try {
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(":id", $id);
            $consulta->execute();

            return $consulta->fetch() ?: null;
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao buscar notícia");
        }
    }

    public function buscarPorTermo(string $termo): array
    {
        $sql = "SELECT id, titulo
                FROM noticias
                WHERE titulo LIKE :termo
                   OR resumo LIKE :termo
                   OR texto LIKE :termo
                ORDER BY data DESC";

        try {
            $consulta = $this->conexao->prepare($sql);
            $consulta->bindValue(":termo", "%" . $termo . "%");
            $consulta->execute();

            return $consulta->fetchAll();
        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao buscar notícias pelo termo informado");
        }
    }

    public function inserir(Noticia $noticia): void
    {
        $sql = "INSERT INTO noticias (
            titulo, texto, resumo, imagem, destaque, usuario_id, categoria_id
        ) VALUES(
            :titulo, :texto, :resumo, :imagem, :destaque, :usuario_id, :categoria_id
        )";

        try {
            $consulta = $this->conexao->prepare($sql);

            $consulta->bindValue(":titulo", $noticia->getTitulo());
            $consulta->bindValue(":texto", $noticia->getTexto());
            $consulta->bindValue(":resumo", $noticia->getResumo());
            $consulta->bindValue(":imagem", $noticia->getImagem());
            $consulta->bindValue(":destaque", $noticia->getDestaque()->value);
            $consulta->bindValue(":usuario_id", $noticia->getUsuarioId());
            $consulta->bindValue(":categoria_id", $noticia->getCategoriaId());

            $consulta->execute();

        } catch (PDOException $e) {
            Utils::registrarLog($e);
            throw new Exception("Erro ao inserir notícia");
        }        
    }
}