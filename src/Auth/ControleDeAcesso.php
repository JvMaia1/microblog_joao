<?php

namespace Microblog\Auth;

// Sessoẽs no PHP
// Funcionalidade usada para controlar o acesso 
// a determinadas páginas e recursos de um site/aplicação.

final class ControleDeAcesso
{
    private function __construct()
    {
        
    }

    private static function iniciar_sessao()
    {
        if(session_status() !== PHP_SESSION_ACTIVE){
            session_start();
        }
    }

    private static function encerrarSessao():void
    {
        $_SESSION = [];
        session_destroy();
    }
    
    public static function exigirLogin():void
        self::iniciar_sessao();
        if(!isset ($_SESSION['id']))
        {
            header("locaion:../login.php?acesso_proibido");
            exit;
        }
    }
}
