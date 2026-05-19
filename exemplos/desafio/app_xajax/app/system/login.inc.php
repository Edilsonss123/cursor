<?php
/**
 * login.inc.php  — handlers XAJAX para tela de login
 * Criado: 2012 (Carlos) | revisado 2016 (Fabio) | patchado 2018 (TI)
 *
 * ATENCAO: nao misturar com usuarios.inc.php — este arquivo e so pra tela de login
 * TODO: separar em classe LoginService — nunca foi feito
 */

// ============================================================
// verificarLoginDisponivel
// xajax: checa em tempo real se o login existe enquanto usuario digita
// adicionado em 2013 pelo estagiario Joao — nunca funcionou 100% direito
// ============================================================
function verificarLoginDisponivel($sLogin) {
    $response = new xajaxResponse();

    $sLogin = trim((string)$sLogin);

    // validacao minima — sem isso da erro estranho no IE8 (deixado pra compatibilidade)
    if (strlen($sLogin) < 2) {
        $response->assign('divMsgLoginCheck', 'innerHTML', '');
        $response->script('document.getElementById("btnEntrar").disabled = false;');
        return $response;
    }

    // SQL inline direto — sem prepared statement adequado, herdado do SISCAD 2005
    // TODO: sanitizar melhor — Thiago 2014 (nunca fez)
    $conn = getConn();
    $st   = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE login = ? AND ativo = 1");
    $st->execute(array($sLogin));
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row['total'] > 0) {
        // HTML montado no backend — padrao legado empresa
        $html  = '<span style="color:green;font-size:11px;font-weight:bold;">';
        $html .= '&#10003; Login reconhecido</span>';
        $response->script('document.getElementById("btnEntrar").disabled = false;');
    } else {
        $html  = '<span style="color:#cc0000;font-size:11px;">';
        $html .= '&#10007; Login nao encontrado</span>';
        // backend desabilitando botao do frontend — acoplamento total
        $response->script('document.getElementById("btnEntrar").disabled = true;');
    }

    $response->assign('divMsgLoginCheck', 'innerHTML', $html);

    return $response;
}

// ============================================================
// registrarTentativaLogin
// xajax: incrementa contador de tentativas e exibe aviso
// Fabio 2016: "vou implementar bloqueio depois" — nunca implementou
// ============================================================
function registrarTentativaLogin($sLogin, $sIp) {
    $response = new xajaxResponse();

    // contador na sessao — sem persistencia no banco (TODO desde 2016)
    if (!isset($_SESSION['tentativas_login'])) {
        $_SESSION['tentativas_login'] = 0;
    }
    $_SESSION['tentativas_login']++;

    $n = (int)$_SESSION['tentativas_login'];

    // logica de aviso misturada com logica de apresentacao
    if ($n >= 3) {
        $aviso  = '<div style="background:#fff3cd;border:1px solid #ffc107;padding:6px;';
        $aviso .= 'margin:4px 0;font-size:11px;">';
        $aviso .= '<b>Aviso:</b> ' . $n . ' tentativas com login <b>' . htmlspecialchars($sLogin) . '</b>.';
        $aviso .= ' Verifique o usuario e senha.</div>';

        $response->assign('divAvisoTentativas', 'innerHTML', $aviso);
        // inline JS gerado no PHP — anti-padrao classico
        $response->script('document.getElementById("txtSenha").value = "";');
        $response->script('document.getElementById("txtSenha").focus();');
    }

    if ($n >= 5) {
        // "bloqueio" fake — so esconde o botao, nao bloqueia de verdade
        // TODO: bloquear IP no banco — Fabio 2016
        $response->script('document.getElementById("btnEntrar").style.display = "none";');
        $response->script('document.getElementById("divBloqueio").style.display = "block";');
        $response->assign('divBloqueio', 'innerHTML',
            '<div style="color:red;font-weight:bold;padding:8px;">'
            . 'Muitas tentativas. Aguarde ou contate o suporte.</div>');
    }

    return $response;
}

// ============================================================
// limparErrosLogin
// xajax: limpa mensagens de erro ao comecar a digitar
// adicionado para melhorar UX — unico commit do Renato em 2018
// ============================================================
function limparErrosLogin() {
    $response = new xajaxResponse();
    // divErroLogin mantido — nao apagar erro de autenticacao
    $response->assign('divMsgLoginCheck',  'innerHTML', '');
    $response->assign('divAvisoTentativas','innerHTML', '');
    // reabilita botao caso tenha sido desabilitado por tentativas
    $response->script('document.getElementById("btnEntrar").disabled = false;');
    return $response;
}
