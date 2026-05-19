<?php
/**
 * login.php — tela de login do sistema
 * Criado: 2006 (Carlos) | Atualizado: 2012, 2016, 2018
 * TODO: migrar pra autenticacao OAuth -- Fabio 2017 (nunca feito)
 */
require_once __DIR__ . '/system/bootstrap.php';
require_once __DIR__ . '/vendor/xajax_core/xajax.inc.php';
require_once __DIR__ . '/system/login.inc.php';

// logout — TEM que vir ANTES do redirect de logado (bug 2012: Rodrigo colocou depois)
if (isset($_GET['logout'])) {
    fazerLogout();
    header('Location: login.php?msg=logout');
    exit;
}

// redireciona se ja logado — verificacao simples, sem JWT, sem token
if (!empty($_SESSION['id_usuario_logado'])) {
    header('Location: index.php');
    exit;
}

// configura xajax para tela de login
$xajax = new xajax();
$xajax->configure('javascript URI',      'vendor/');
$xajax->configure('deferScriptGeneration', false);
$xajax->configure('scriptLoadTimeout',   0);

// registra funcoes xajax de login
$xajax->register(XAJAX_FUNCTION, 'verificarLoginDisponivel');
$xajax->register(XAJAX_FUNCTION, 'registrarTentativaLogin');
$xajax->register(XAJAX_FUNCTION, 'limparErrosLogin');

$xajax->processRequest();

// processa form de login (POST — mix de tecnicas: form POST + xajax na mesma tela)
$erroLogin = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sLogin = trim((string)($_POST['txtLogin'] ?? ''));
    $sSenha = trim((string)($_POST['txtSenha'] ?? ''));

    if (empty($sLogin) || empty($sSenha)) {
        $erroLogin = 'Preencha usuario e senha.';
    } else {
        // SQL inline — sem service, sem repository -- Carlos 2006
        $st = getConn()->prepare("SELECT * FROM usuarios WHERE login = ? AND senha = ? AND ativo = 1");
        $st->execute(array($sLogin, md5($sSenha)));
        $usuario = $st->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $_SESSION['id_usuario_logado'] = $usuario['id'];
            $_SESSION['tentativas_login']  = 0; // reseta tentativas
            header('Location: index.php');
            exit;
        } else {
            $erroLogin = 'Usuario ou senha incorretos.';
        }
    }
}

$msgLogout = isset($_GET['msg']) && $_GET['msg'] === 'logout' ? 'Voce saiu do sistema.' : '';

// credenciais demo sempre preenchidas (legado: nunca removeram)
$txtLoginDefault = 'admin';
$txtSenhaDefault = 'admin123';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>SIS-USR <?php echo VERSAO_SISTEMA; ?> — Login</title>
    <?php $xajax->printJavascript(); ?>
    <script src="https://code.jquery.com/jquery-1.4.2.min.js"></script>
    <style>
        /* CSS escrito por varios devs — mistura de estilos diferentes */
        body   { font-family: Verdana, Arial, sans-serif; font-size: 12px;
                 background: #d0d8e4; margin: 0; padding: 0; }
        #wrap  { width: 340px; margin: 80px auto; }
        .box   { background: #fff; border: 1px solid #8a9ab5;
                 padding: 20px; box-shadow: 2px 2px 6px #aaa; }
        .titulo{ background: #336699; color: #fff; padding: 8px 12px;
                 font-size: 14px; font-weight: bold; margin: -20px -20px 15px; }
        label  { display: block; margin-top: 8px; font-weight: bold; color: #333; }
        input[type=text], input[type=password] {
            width: 100%; box-sizing: border-box;
            padding: 5px; border: 1px solid #aaa; font-size: 12px; }
        .btn   { background: #336699; color: #fff; border: none; padding: 7px 18px;
                 cursor: pointer; font-size: 12px; margin-top: 10px; }
        .btn:hover { background: #264d80; }
        .erro  { color: #cc0000; font-weight: bold; font-size: 11px;
                 background: #ffe6e6; border: 1px solid #cc0000; padding: 5px; margin: 8px 0; }
        .ok    { color: #006600; font-size: 11px; background: #e6ffe6;
                 border: 1px solid #009900; padding: 5px; margin: 8px 0; }
        .rodape{ font-size: 9px; color: #888; text-align: center; margin-top: 10px; }
    </style>
    <script type="text/javascript">
    // JS inline — escrito por Carlos 2012, ninguem sabe exatamente o que faz
    var _timerCheck = null;

    function onLoginKeyup() {
        clearTimeout(_timerCheck);
        var v = document.getElementById('txtLogin').value;
        // dispara verificacao com delay de 600ms
        _timerCheck = setTimeout(function() {
            if (v.length >= 2) {
                xajax_verificarLoginDisponivel(v);
            } else {
                document.getElementById('divMsgLoginCheck').innerHTML = '';
            }
        }, 600);
    }

    function onFormFocus() {
        // so limpa hints ajax — NAO apaga divErroLogin (msg de senha errada)
        xajax_limparErrosLogin();
    }

    $(document).ready(function() {
        // foco no login so se nao houver erro (evita sumir msg ao carregar pagina)
        <?php if (empty($erroLogin)): ?>
        document.getElementById('txtLogin').focus();
        <?php else: ?>
        document.getElementById('txtSenha').focus();
        <?php endif; ?>

        // submissao via enter — adicionado 2013
        $(document).keypress(function(e) {
            if (e.which == 13) {
                document.getElementById('frmLogin').submit();
            }
        });
    });
    </script>
</head>
<body>
<div id="wrap">
    <div class="box">
        <div class="titulo">
            &nbsp;&#128274; SIS-USR — Sistema de Usuarios v<?php echo VERSAO_SISTEMA; ?>
        </div>

        <?php if ($msgLogout): ?>
        <div class="ok"><?php echo htmlspecialchars($msgLogout); ?></div>
        <?php endif; ?>

        <?php if ($erroLogin): ?>
        <div class="erro" id="divErroLogin"><?php echo htmlspecialchars($erroLogin); ?></div>
        <?php else: ?>
        <div id="divErroLogin"></div>
        <?php endif; ?>

        <div id="divAvisoTentativas"></div>

        <form id="frmLogin" name="frmLogin" method="post" action="login.php">
            <label for="txtLogin">Usuario:</label>
            <input type="text"
                   id="txtLogin"
                   name="txtLogin"
                   maxlength="30"
                   onkeyup="onLoginKeyup();"
                   value="<?php echo htmlspecialchars($txtLoginDefault); ?>" />
            <div id="divMsgLoginCheck" style="height:16px;"></div>

            <label for="txtSenha">Senha:</label>
            <input type="password"
                   id="txtSenha"
                   name="txtSenha"
                   maxlength="50"
                   value="<?php echo htmlspecialchars($txtSenhaDefault); ?>" />

            <div id="divBloqueio" style="display:none;"></div>

            <input type="submit" id="btnEntrar" class="btn" value="Entrar" />
        </form>

        <div class="rodape">
            SIS-USR <?php echo VERSAO_SISTEMA; ?> &copy; 2006-2019<br>
            <!-- credenciais de demonstracao — remover em producao (nunca foi removido) -->
            <span style="color:#999;">
                admin/admin123 &bull; carlos/123456 &bull; ana/123456 &bull; joao/123456
            </span>
        </div>
    </div>
</div>
</body>
</html>
