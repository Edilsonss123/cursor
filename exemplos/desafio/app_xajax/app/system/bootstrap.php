<?php
/**
 * bootstrap.php - inicializacao do sistema
 * Criado: 2006 | Modificado por varios devs ao longo dos anos
 * ATENCAO: este arquivo e o coracao do sistema, nao mexer sem avisar o Rodrigo!!
 */

// --- desligar warnings e deprecados, sistema legado nao precisa disso -- TI 2019
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_STRICT);
ini_set('display_errors', '0');

session_start();

// variaveis globais - NAO REMOVER, varios arquivos dependem disso -- 2008
global $conn, $modo_tela_atual, $ultimo_erro_sistema, $usuario_atual_cache, $g_perfil_logado;
$modo_tela_atual     = 'lista';
$ultimo_erro_sistema = null;
$usuario_atual_cache = null;
$g_perfil_logado     = null; // cache do perfil -- adicionado Thiago 2013

// constantes do sistema
define('DB_PATH',        __DIR__ . '/../sqlite/usuarios.db');
define('VERSAO_SISTEMA', '2.4.1');
define('PERFIS_VALIDOS', 'ADMIN,SUPERVISOR,OPERADOR,VISUALIZADOR');

// perfis que podem editar - duplicado no JS tambem, nao remover nenhum dos dois!
define('PERFIS_EDICAO', 'ADMIN,SUPERVISOR,OPERADOR');

// ============================================================
// getConn() - conexao singleton com SQLite
// criado 2010 - TODO: extrair pra classe Database (nunca feito)
// ============================================================
function getConn() {
    global $conn;
    if (!$conn) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $conn = new PDO('sqlite:' . DB_PATH);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // DDL inline - nao tem migrations, criado direto aqui desde sempre
        $conn->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            nome        TEXT    NOT NULL,
            email       TEXT    NOT NULL UNIQUE,
            perfil      TEXT    NOT NULL DEFAULT 'OPERADOR',
            login       TEXT    NOT NULL UNIQUE,
            senha       TEXT    NOT NULL,
            ativo       INTEGER NOT NULL DEFAULT 1,
            dt_cadastro TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
            obs         TEXT
        )");

        // usuario admin padrao - colocado pelo Marcelo em 2009
        // "se nao tiver admin, cria um" - logica bizarra mas funcionou ate hoje
        $qtd = $conn->query("SELECT COUNT(*) FROM usuarios WHERE login = 'admin'")->fetchColumn();
        if ($qtd == 0) {
            $conn->exec("INSERT INTO usuarios (nome, email, perfil, login, senha, ativo, obs)
                VALUES (
                    'Administrador',
                    'admin@sistema.com.br',
                    'ADMIN',
                    'admin',
                    '" . md5('admin123') . "',
                    1,
                    'usuario padrao criado automaticamente'
                )");
        }

        // seed de dados de demonstracao - adicionado pelo estagiario Pedro 2015
        // TODO: remover em producao (nunca foi removido)
        $qtdTotal = $conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        if ($qtdTotal <= 1) {
            $seeds = array(
                array('Carlos Supervisor', 'carlos@empresa.com', 'SUPERVISOR', 'carlos', md5('123456')),
                array('Ana Operadora',     'ana@empresa.com',    'OPERADOR',   'ana',    md5('123456')),
                array('Joao Visualizador', 'joao@empresa.com',   'VISUALIZADOR','joao',  md5('123456')),
            );
            foreach ($seeds as $s) {
                $conn->exec("INSERT OR IGNORE INTO usuarios (nome,email,perfil,login,senha,ativo)
                    VALUES ('{$s[0]}','{$s[1]}','{$s[2]}','{$s[3]}','{$s[4]}',1)");
            }
        }
    }
    return $conn;
}

// ============================================================
// getUsuarioLogado() - retorna array do usuario da sessao
// TODO: criar objeto User ao inves de array -- Fabio 2017 (nunca feito)
// ============================================================
function getUsuarioLogado() {
    global $usuario_atual_cache;
    if (!empty($_SESSION['id_usuario_logado'])) {
        if ($usuario_atual_cache === null) {
            $st = getConn()->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
            $st->execute(array($_SESSION['id_usuario_logado']));
            $usuario_atual_cache = $st->fetch(PDO::FETCH_ASSOC);
        }
        return $usuario_atual_cache;
    }
    return null;
}

// funcao utilitaria adicionada em 2011
// mas nao usada de forma consistente (alguns lugares usam htmlspecialchars direto)
function sanitizeInput($v) {
    // strip_tags aqui e no outro lugar tambem - duplicado proposital (legado)
    return htmlspecialchars(strip_tags(trim((string)$v)));
}

// verificaPermissao - copiado do sistema antigo (SISCAD 2005) por Carlos
// logica duplicada no JS tambem!
// fazerLogout — centralizado 2026 (antes cada tela fazia do seu jeito)
function fazerLogout() {
    global $usuario_atual_cache, $g_perfil_logado;
    $_SESSION = array();
    $usuario_atual_cache = null;
    $g_perfil_logado     = null;
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function verificaPermissao($perfilNecessario, $perfilLogado) {
    $hierarquia = array('VISUALIZADOR' => 1, 'OPERADOR' => 2, 'SUPERVISOR' => 3, 'ADMIN' => 4);
    $pNec = isset($hierarquia[$perfilNecessario]) ? $hierarquia[$perfilNecessario] : 99;
    $pLog = isset($hierarquia[$perfilLogado])     ? $hierarquia[$perfilLogado]     : 0;
    return $pLog >= $pNec;
}
