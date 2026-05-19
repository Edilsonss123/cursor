<?php
/**
 * index.php — tela unica: lista / novo / editar / visualizar usuarios
 * Criado: 2007 (Carlos) | Modificado: 2009, 2011, 2013, 2015, 2018, 2019
 *
 * !!! ARQUIVO PRINCIPAL — nao mexer sem avisar equipe !!!
 * Contato: rodrigo@empresa.com (saiu em 2020)
 */
require_once __DIR__ . '/system/bootstrap.php';

// logout ANTES de carregar xajax — link "Sair" aponta aqui
if (isset($_GET['logout'])) {
    fazerLogout();
    header('Location: login.php?msg=logout');
    exit;
}

require_once __DIR__ . '/vendor/xajax_core/xajax.inc.php';
require_once __DIR__ . '/system/usuarios.inc.php';  // handlers xajax de usuarios

// protecao de sessao — simples, sem middleware, sem JWT
if (empty($_SESSION['id_usuario_logado'])) {
    header('Location: login.php');
    exit;
}

// ================================================================
// CONFIGURACAO XAJAX
// ================================================================
$xajax = new xajax();
$xajax->configure('javascript URI',       'vendor/');
$xajax->configure('deferScriptGeneration', false);
$xajax->configure('scriptLoadTimeout',    0);

// registra todas as funcoes xajax de usuarios
$xajax->register(XAJAX_FUNCTION, 'listarUsuariosDiv');
$xajax->register(XAJAX_FUNCTION, 'carregarUsuarioParaEdicao');
$xajax->register(XAJAX_FUNCTION, 'alterarPerfilNaTela');
$xajax->register(XAJAX_FUNCTION, 'aplicarPermissoesCampos');
$xajax->register(XAJAX_FUNCTION, 'validarEmailAjax');
$xajax->register(XAJAX_FUNCTION, 'mudarModoTelaAjax');
$xajax->register(XAJAX_FUNCTION, 'excluirUsuario');
$xajax->register(XAJAX_FUNCTION, 'salvarUsuario');

$xajax->processRequest();

// ================================================================
// PREPARA DADOS DA TELA (PHP misturado com logica de apresentacao)
// ================================================================
$uLog         = getUsuarioLogado();
$perfilLogado = $uLog ? strtoupper($uLog['perfil']) : 'VISUALIZADOR';

// modo via querystring — padrao legado, sem roteamento
global $modo_tela_atual;
$modo = $modo_tela_atual;
if (!empty($_GET['acao'])) {
    $modo = $_GET['acao'];
}

$idQs        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$usuarioEdit = null;
if ($idQs > 0) {
    // SQL inline — sem repository — padrao empresa
    $st = getConn()->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
    $st->execute(array($idQs));
    $usuarioEdit = $st->fetch(PDO::FETCH_ASSOC);
}

// campos habilitados baseados em modo e perfil — logica duplicada no JS
$disabledGlobal = ($modo === 'ver' || $perfilLogado === 'VISUALIZADOR') ? 'disabled' : '';
$showBtnSalvar  = ($perfilLogado !== 'VISUALIZADOR' && $modo !== 'ver');
// edicao: readonly envia valor no form; disabled NAO (bug xajax getFormValues)
$txtLoginAttr = $disabledGlobal ? 'disabled' : (($idQs > 0 && $modo !== 'novo') ? 'readonly' : '');

// titulo dinamico — sem template engine, echo puro
$tituloPagina = 'CADASTRO DE USUARIOS';
if ($modo === 'editar') $tituloPagina = 'EDITAR USUARIO';
if ($modo === 'ver')    $tituloPagina = 'VISUALIZAR USUARIO';
if ($modo === 'novo')   $tituloPagina = 'NOVO USUARIO';

// lista inicial de usuarios — HTML montado no PHP antes de renderizar
$htmlListaInicial = montarHtmlListaUsuarios('');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $tituloPagina; ?> — SIS-USR <?php echo VERSAO_SISTEMA; ?></title>
    <?php $xajax->printJavascript(); ?>
    <script src="https://code.jquery.com/jquery-1.4.2.min.js"></script>
    <style>
        /* CSS de varios devs — mistura de estilos, sem framework */
        body       { font-family: Verdana, Arial; font-size: 12px; margin: 8px;
                     background: #e0e8f0; }
        h2         { font-size: 14px; color: #336699; margin: 0 0 6px; }
        .painel    { background: #fff; border: 1px solid #8a9ab5; padding: 10px;
                     margin-bottom: 8px; }
        .titulo-painel { background: #336699; color: #fff; padding: 5px 10px;
                         font-weight: bold; font-size: 12px; margin: -10px -10px 10px; }
        label      { font-weight: bold; display: inline-block; width: 100px;
                     color: #333; font-size: 11px; }
        input[type=text], input[type=password], select, textarea {
            border: 1px solid #aaa; padding: 3px; font-size: 11px; }
        .btn       { background: #336699; color: #fff; border: 1px solid #264d80;
                     padding: 4px 12px; cursor: pointer; font-size: 11px; }
        .btn:hover { background: #264d80; }
        .btn-novo  { background: #009900; border-color: #006600; }
        .btn-novo:hover { background: #006600; }
        .btn-perig { background: #cc0000; border-color: #990000; }
        .btn-perig:hover { background: #990000; }
        .rodape    { font-size: 9px; color: #888; border-top: 1px solid #ccc;
                     padding-top: 5px; margin-top: 10px; }
        .campo-linha { margin-bottom: 6px; }
        #divStatus { min-height: 20px; }
        input[readonly] { background: #eee; color: #555; }
    </style>
    <script type="text/javascript">
    // ================================================================
    // JS INLINE — regras de permissao DUPLICADAS do backend!
    // "tem que validar no cliente tambem" -- Carlos 2007
    // ================================================================
    var PERFIL_LOGADO = '<?php echo $perfilLogado; ?>';
    var MODO_TELA     = '<?php echo htmlspecialchars($modo); ?>';

    // funcao de badge de perfil — usada por varios lugares (HTML inline)
    function atualizarBadgePerfil(p) {
        var cores = {
            'ADMIN':'#cc0000', 'SUPERVISOR':'#0066cc',
            'OPERADOR':'#009900', 'VISUALIZADOR':'#888'
        };
        var cor = cores[p] || '#333';
        document.getElementById('divBadgePerfil').innerHTML =
            'Perfil: <b style="color:' + cor + ';">' + p + '</b>';
    }

    // recarrega lista — chama xajax diretamente do JS
    function recarregarLista() {
        var filtro = document.getElementById('txtFiltro') ?
                     document.getElementById('txtFiltro').value : '';
        xajax_listarUsuariosDiv(filtro);
    }

    // validacao client-side DUPLICADA da server-side em salvarUsuario
    function validarFormularioJS() {
        var nome  = document.getElementById('txtNome').value;
        var email = document.getElementById('txtEmail').value;
        var login = document.getElementById('txtLogin').value;
        var modo  = document.getElementById('hdnModoAtual').value;

        if (nome == '' || nome.length < 3) {
            alert('Nome obrigatorio (minimo 3 caracteres)');
            document.getElementById('txtNome').focus();
            return false;
        }
        if (email == '' || email.indexOf('@') < 0) {
            alert('Email invalido');
            document.getElementById('txtEmail').focus();
            return false;
        }
        if (login == '' && modo == 'novo') {
            alert('Login obrigatorio');
            document.getElementById('txtLogin').focus();
            return false;
        }
        // permissao duplicada aqui tambem!
        if (PERFIL_LOGADO === 'VISUALIZADOR') {
            alert('Sem permissao para salvar (VISUALIZADOR)');
            return false;
        }
        return true;
    }

    // click no salvar — valida JS antes de chamar xajax
    function clickSalvar() {
        if (!validarFormularioJS()) return;
        xajax_salvarUsuario(xajax.getFormValues('frmUsuario'));
    }

    // click novo usuario
    function clickNovo() {
        xajax_mudarModoTelaAjax('novo');
    }

    // onchange do select de perfil — chama backend via xajax
    function onChangePerfil(v) {
        xajax_alterarPerfilNaTela(v);
    }

    // onblur do email — validacao em tempo real via xajax
    function onBlurEmail() {
        var email = document.getElementById('txtEmail').value;
        var id    = document.getElementById('hdnIdUsuario').value;
        if (email.length > 3) {
            xajax_validarEmailAjax(email, id);
        }
    }

    $(document).ready(function() {
        // aplica permissoes ao carregar — xajax na inicializacao
        xajax_aplicarPermissoesCampos(xajax.getFormValues('frmUsuario'));

        // esconde botoes baseado no perfil — logica TRIPLICADA (JS + PHP + Backend xajax)
        if (PERFIL_LOGADO === 'VISUALIZADOR') {
            document.getElementById('btnSalvar').style.display = 'none';
            document.getElementById('btnNovo').style.display   = 'none';
        }
        if (PERFIL_LOGADO === 'OPERADOR') {
            document.getElementById('selPerfil').disabled = true;
        }
        if (MODO_TELA === 'ver') {
            var els = document.getElementById('frmUsuario').elements;
            for (var i = 0; i < els.length; i++) { els[i].disabled = true; }
            document.getElementById('btnSalvar').style.display = 'none';
        }
        <?php if ($idQs > 0 && $usuarioEdit): ?>
        // carrega usuario se veio via querystring — 2009
        xajax_carregarUsuarioParaEdicao(<?php echo $idQs; ?>, '<?php echo $modo === 'ver' ? 'ver' : 'editar'; ?>');
        <?php endif; ?>
    });
    </script>
</head>
<body>

<!-- barra de topo — HTML misto com PHP -->
<div style="background:#336699;color:#fff;padding:6px 10px;margin:-8px -8px 8px;">
    <b>SIS-USR</b> v<?php echo VERSAO_SISTEMA; ?>
    &nbsp;|&nbsp; Usuario: <b><?php echo htmlspecialchars($uLog['nome'] ?? ''); ?></b>
    &nbsp;|&nbsp; <span id="spnPerfilLogado"><?php echo $perfilLogado; ?></span>
    &nbsp;|&nbsp; <a href="logout.php" style="color:#ffcc00;">Sair</a>
</div>

<!-- painel principal -->
<div class="painel">
    <div class="titulo-painel">LISTA DE USUARIOS</div>

    <!-- filtro e botao novo — JS inline misturado -->
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td>
            <input type="text" id="txtFiltro" name="txtFiltro"
                   placeholder="Filtrar por nome, email ou login..."
                   style="width:280px;"
                   onkeyup="recarregarLista();" />
            &nbsp;
            <button class="btn" onclick="recarregarLista();">Buscar</button>
        </td>
        <td align="right">
            <?php if ($perfilLogado !== 'VISUALIZADOR'): ?>
            <button id="btnNovo" class="btn btn-novo" onclick="clickNovo();">+ Novo Usuario</button>
            <?php endif; ?>
        </td>
    </tr></table>

    <div style="margin-top:8px;" id="divUsuarios">
        <!-- lista inicial montada no PHP — nao e xajax na primeira carga -->
        <?php echo $htmlListaInicial; ?>
    </div>
</div>

<!-- painel de status — mensagens de retorno xajax -->
<div id="divStatus"></div>

<!-- painel do formulario — escondido inicialmente -->
<div id="divFormUsuario" class="painel"
     style="display:<?php echo ($idQs > 0 || $modo === 'novo') ? 'block' : 'none'; ?>;">

    <div class="titulo-painel" id="divTituloForm">
        <?php
        // titulo dinamico — if/else inline sem funcao auxiliar
        if ($modo === 'novo')   echo 'NOVO USUARIO';
        elseif ($modo === 'ver') echo 'VISUALIZAR USUARIO';
        else                    echo 'EDITAR USUARIO';
        ?>
    </div>

    <form id="frmUsuario" name="frmUsuario" method="post" action="">
        <!-- campos hidden controlando fluxo — anti-padrao classico -->
        <input type="hidden" id="hdnIdUsuario"      name="hdnIdUsuario"      value="<?php echo $idQs; ?>" />
        <input type="hidden" id="hdnModoAtual"       name="hdnModoAtual"       value="<?php echo htmlspecialchars($modo); ?>" />
        <input type="hidden" id="hdnPerfilOriginal"  name="hdnPerfilOriginal"  value="<?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['perfil']) : ''; ?>" />
        <input type="hidden" id="hdnLogin" name="hdnLogin" value="<?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['login']) : ''; ?>" />

        <!-- campos do formulario — sem componentes, HTML puro -->
        <div class="campo-linha">
            <label for="txtNome">Nome: *</label>
            <input type="text" id="txtNome" name="txtNome" size="40" maxlength="100"
                   <?php echo $disabledGlobal; ?>
                   value="<?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['nome']) : ''; ?>" />
        </div>

        <div class="campo-linha">
            <label for="txtEmail">Email: *</label>
            <input type="text" id="txtEmail" name="txtEmail" size="40" maxlength="150"
                   <?php echo $disabledGlobal; ?>
                   onblur="onBlurEmail();"
                   value="<?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['email']) : ''; ?>" />
            <div id="divErroEmail" style="display:inline;"></div>
        </div>

        <div class="campo-linha">
            <label for="txtLogin">Login: *</label>
            <input type="text" id="txtLogin" name="txtLogin" size="20" maxlength="30"
                   <?php echo $txtLoginAttr; ?>
                   value="<?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['login']) : ''; ?>" />
        </div>

        <div class="campo-linha">
            <label for="txtSenha">Senha: <?php echo $idQs > 0 ? '' : '*'; ?></label>
            <input type="password" id="txtSenha" name="txtSenha" size="20" maxlength="50"
                   <?php echo $disabledGlobal; ?>
                   placeholder="<?php echo $idQs > 0 ? 'deixe em branco para nao alterar' : ''; ?>" />
        </div>

        <div class="campo-linha">
            <label for="selPerfil">Perfil: *</label>
            <select id="selPerfil" name="selPerfil"
                    <?php echo $disabledGlobal; ?>
                    onchange="onChangePerfil(this.value);">
                <?php
                // opcoes hardcoded — mesma lista em 4 lugares no sistema!
                $perfisOpts = array('ADMIN', 'SUPERVISOR', 'OPERADOR', 'VISUALIZADOR');
                foreach ($perfisOpts as $p) {
                    $sel = ($usuarioEdit && $usuarioEdit['perfil'] === $p) ? ' selected' : '';
                    if (!$usuarioEdit && $p === 'OPERADOR') $sel = ' selected'; // default
                    echo '<option value="' . $p . '"' . $sel . '>' . $p . '</option>';
                }
                ?>
            </select>
            &nbsp;
            <span id="divBadgePerfil">
                Perfil: <b><?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['perfil']) : 'OPERADOR'; ?></b>
            </span>
        </div>

        <div class="campo-linha">
            <label for="txtObs">Obs:</label>
            <textarea id="txtObs" name="txtObs" rows="2" cols="40"
                      <?php echo $disabledGlobal; ?>><?php echo $usuarioEdit ? htmlspecialchars($usuarioEdit['obs'] ?? '') : ''; ?></textarea>
        </div>

        <!-- aviso de permissao — preenchido via xajax -->
        <div id="divAvisoPermissao"></div>

        <!-- botoes — visibilidade controlada por PHP e JS (duplicado!) -->
        <div style="margin-top:10px;">
            <?php if ($showBtnSalvar): ?>
            <button type="button" id="btnSalvar" class="btn" onclick="clickSalvar();">Salvar</button>
            <?php else: ?>
            <button type="button" id="btnSalvar" class="btn" style="display:none;" onclick="clickSalvar();">Salvar</button>
            <?php endif; ?>
            &nbsp;
            <button type="button" class="btn"
                    onclick="xajax_mudarModoTelaAjax('lista');"
                    style="background:#888;">Cancelar</button>
        </div>
    </form>
</div>

<div class="rodape">
    SIS-USR <?php echo VERSAO_SISTEMA; ?> &copy; 2006-2019 &mdash;
    Sistema desenvolvido pela equipe de TI &mdash;
    <!-- comentario antigo deixado pelo Carlos 2008: "TODO: adicionar paginacao" -->
    Em caso de problemas contate: rodrigo@empresa.com (saiu em 2020)
</div>

</body>
</html>
