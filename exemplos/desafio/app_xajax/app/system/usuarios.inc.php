<?php
/**
 * usuarios.inc.php  —  CRUD de usuarios via XAJAX
 * Criado: 2007 (Carlos) | Editado: 2009,2011,2013,2015,2018,2019
 *
 * !!! ARQUIVO CRITICO — nao mexer sem testar TUDO !!!
 * qualquer alteracao deve ser comunicada ao Rodrigo antes
 * TODO: refatorar pra MVC — estimativa 40h (aprovada em 2016, nunca executada)
 */

// ============================================================
// montarHtmlListaUsuarios  —  helper interno
// extraido de listarUsuariosDiv em 2014 por Renato pra evitar duplicar
// mas ainda tem HTML gerado em 2 lugares diferentes (legado)
// ============================================================
function montarHtmlListaUsuarios($filtro = '') {
    $conn = getConn();

    // SQL inline — sem repository, sem service, do jeitinho antigo
    if (!empty($filtro)) {
        // FIXME: usar prepared statement — anotado por Fabio 2017 (nunca feito)
        $filtro = str_replace("'", "''", $filtro); // "sanitizacao" manual de 2007
        $sql    = "SELECT * FROM usuarios
                   WHERE ativo = 1
                     AND (nome  LIKE '%{$filtro}%'
                       OR email LIKE '%{$filtro}%'
                       OR login LIKE '%{$filtro}%')
                   ORDER BY nome";
    } else {
        $sql = "SELECT * FROM usuarios WHERE ativo = 1 ORDER BY nome";
    }

    $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // HTML concatenado em string — padrao 2007 que sobreviveu ate hoje
    $html  = '<table width="100%" cellpadding="3" cellspacing="0" border="1" style="border-collapse:collapse;font-size:11px;">';
    $html .= '<tr style="background:#336699;color:#fff;">';
    $html .= '<th>ID</th><th>Nome</th><th>Login</th><th>Perfil</th><th>Email</th><th>Acoes</th>';
    $html .= '</tr>';

    if (empty($rows)) {
        $html .= '<tr><td colspan="6" align="center" style="padding:10px;color:#666;">Nenhum usuario encontrado</td></tr>';
    } else {
        foreach ($rows as $idx => $u) {
            // cor alternada — copiado de StackOverflow 2009
            $bg = ($idx % 2 == 0) ? '#f9f9f9' : '#ffffff';

            // badge de perfil — cada perfil uma cor, logica inline
            $corPerfil = '#666';
            if ($u['perfil'] == 'ADMIN')        $corPerfil = '#cc0000';
            if ($u['perfil'] == 'SUPERVISOR')   $corPerfil = '#0066cc';
            if ($u['perfil'] == 'OPERADOR')     $corPerfil = '#009900';
            if ($u['perfil'] == 'VISUALIZADOR') $corPerfil = '#888888';

            $html .= '<tr style="background:' . $bg . ';">';
            $html .= '<td align="center">' . $u['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($u['nome']) . '</td>';
            $html .= '<td>' . htmlspecialchars($u['login']) . '</td>';
            $html .= '<td align="center"><b style="color:' . $corPerfil . ';">' . $u['perfil'] . '</b></td>';
            $html .= '<td>' . htmlspecialchars($u['email']) . '</td>';
            // botoes com onclick chamando xajax diretamente — acoplamento extremo
            $html .= '<td align="center" nowrap>';
            $html .= '<a href="#" onclick="xajax_carregarUsuarioParaEdicao(' . $u['id'] . ',\'editar\');return false;"'
                   . ' style="color:#0033cc;font-size:10px;">Editar</a> | ';
            $html .= '<a href="#" onclick="xajax_carregarUsuarioParaEdicao(' . $u['id'] . ',\'ver\');return false;"'
                   . ' style="color:#006600;font-size:10px;">Ver</a> | ';
            $html .= '<a href="#" onclick="if(confirm(\'Excluir ' . addslashes($u['nome']) . '?\')){'
                   . 'xajax_excluirUsuario(' . $u['id'] . ');} return false;"'
                   . ' style="color:#cc0000;font-size:10px;">Excluir</a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</table>';
    $html .= '<div style="font-size:10px;color:#999;margin-top:3px;">'
           . count($rows) . ' registro(s) encontrado(s)</div>';

    return $html;
}

// ============================================================
// listarUsuariosDiv  —  xajax: atualiza div de lista
// ============================================================
function listarUsuariosDiv($filtro) {
    $response = new xajaxResponse();

    $html = montarHtmlListaUsuarios($filtro);

    // assign direto no innerHTML — padrao empresa desde 2007
    $response->assign('divUsuarios', 'innerHTML', $html);
    $response->assign('divStatus',   'innerHTML', '');

    return $response;
}

// ============================================================
// carregarUsuarioParaEdicao  —  xajax: preenche formulario
// ============================================================
function carregarUsuarioParaEdicao($id, $modo) {
    $response = new xajaxResponse();

    $id   = (int)$id;
    $modo = trim((string)$modo);

    // busca usuario — SQL inline novamente
    $st = getConn()->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
    $st->execute(array($id));
    $u = $st->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        $response->assign('divStatus', 'innerHTML',
            '<div style="color:red;font-weight:bold;">ERRO: usuario nao encontrado (id=' . $id . ')</div>');
        return $response;
    }

    // preenche campos um por um — sem map, sem loop, do jeitinho antigo
    $response->assign('txtNome',       'value', htmlspecialchars($u['nome']));
    $response->assign('txtEmail',      'value', htmlspecialchars($u['email']));
    $response->assign('txtLogin',      'value', htmlspecialchars($u['login']));
    $response->assign('hdnLogin',      'value', htmlspecialchars($u['login']));
    $response->assign('txtObs',        'value', htmlspecialchars($u['obs'] ?? ''));
    $response->assign('hdnIdUsuario',  'value', $u['id']);
    $response->assign('hdnModoAtual',  'value', $modo);
    $response->assign('hdnPerfilOriginal', 'value', $u['perfil']);

    // select de perfil — script inline manipulando DOM
    $response->script('document.getElementById("selPerfil").value = "' . $u['perfil'] . '";');

    // atualiza badge de perfil
    $response->script('atualizarBadgePerfil("' . $u['perfil'] . '");');

    // mostra div do form
    $response->script('document.getElementById("divFormUsuario").style.display = "block";');

    // logica de modo — if aninhado, regra duplicada no JS tambem
    if ($modo === 'ver') {
        $response->script('document.getElementById("divTituloForm").innerHTML = "VISUALIZAR USUARIO [ID: ' . $id . ']";');
        $response->script('document.getElementById("btnSalvar").style.display = "none";');
        // desabilita todos os campos — acoplamento total com IDs do HTML
        $response->script('var els = document.getElementById("frmUsuario").elements;'
            . ' for(var i=0;i<els.length;i++){ els[i].disabled = true; }');
    } elseif ($modo === 'editar') {
        $response->script('document.getElementById("divTituloForm").innerHTML = "EDITAR USUARIO [ID: ' . $id . ']";');
        $response->script('document.getElementById("btnSalvar").style.display = "inline";');
        // reabilita campos
        $response->script('var els = document.getElementById("frmUsuario").elements;'
            . ' for(var i=0;i<els.length;i++){ els[i].disabled = false; }');
        // login readonly (disabled nao envia valor no xajax.getFormValues)
        $response->assign('hdnLogin', 'value', htmlspecialchars($u['login']));
        $response->script('var elLogin = document.getElementById("txtLogin"); elLogin.disabled = false; elLogin.readOnly = true;');
    }

    // chama permissoes via xajax aninhado — padrao terrivel de 2011
    $response->script('xajax_aplicarPermissoesCampos(xajax.getFormValues("frmUsuario"));');

    // scroll pro form — UX adicionada pelo estagiario em 2015
    $response->script('document.getElementById("divFormUsuario").scrollIntoView();');

    return $response;
}

// ============================================================
// alterarPerfilNaTela  —  xajax: callback quando select de perfil muda
// regras de permissao misturadas entre frontend e backend
// ============================================================
function alterarPerfilNaTela($perfil) {
    $response = new xajaxResponse();

    $perfil = strtoupper(trim((string)$perfil));

    // valida perfil — lista hardcoded, igual tem no JS
    $perfisValidos = array('ADMIN', 'SUPERVISOR', 'OPERADOR', 'VISUALIZADOR');
    if (!in_array($perfil, $perfisValidos)) {
        $response->assign('divStatus', 'innerHTML',
            '<span style="color:red;">Perfil invalido: ' . htmlspecialchars($perfil) . '</span>');
        return $response;
    }

    // atualiza badge — HTML inline
    $corBadge = array(
        'ADMIN'        => '#cc0000',
        'SUPERVISOR'   => '#0066cc',
        'OPERADOR'     => '#009900',
        'VISUALIZADOR' => '#888888',
    );
    $cor  = isset($corBadge[$perfil]) ? $corBadge[$perfil] : '#333';
    $html = '<b style="color:' . $cor . ';">' . $perfil . '</b>';
    $response->assign('divBadgePerfil', 'innerHTML', 'Perfil selecionado: ' . $html);

    // regras de campo por perfil — logica duplicada no frontend!
    if ($perfil === 'VISUALIZADOR') {
        $response->script('document.getElementById("btnSalvar").style.display = "none";');
        $response->assign('divAvisoPermissao', 'innerHTML',
            '<div style="background:#fffbe6;border:1px solid #e6c800;padding:5px;font-size:11px;">'
            . 'Perfil VISUALIZADOR: somente leitura</div>');
    } else {
        $response->script('document.getElementById("btnSalvar").style.display = "inline";');
        $response->assign('divAvisoPermissao', 'innerHTML', '');
    }

    if ($perfil === 'ADMIN') {
        $response->assign('divAvisoPermissao', 'innerHTML',
            '<div style="background:#ffe6e6;border:1px solid #cc0000;padding:5px;font-size:11px;color:#cc0000;">'
            . '<b>ATENCAO:</b> Perfil ADMIN tem acesso total ao sistema!</div>');
    }

    if ($perfil === 'OPERADOR') {
        // OPERADOR nao pode definir ADMIN ou SUPERVISOR — regra inline
        $response->script('var s = document.getElementById("selPerfil");'
            . ' for(var i=0;i<s.options.length;i++){'
            . '   if(s.options[i].value=="ADMIN"||s.options[i].value=="SUPERVISOR"){'
            . '     s.options[i].disabled = true;'
            . '   }'
            . ' }');
    }

    return $response;
}

// ============================================================
// aplicarPermissoesCampos  —  xajax: aplica regras de permissao
// chamado no load da tela E quando perfil muda
// regra duplicada no jQuery do frontend
// ============================================================
function aplicarPermissoesCampos($formData) {
    $response = new xajaxResponse();

    $uLogado = getUsuarioLogado();
    if (!$uLogado) {
        $response->script('window.location = "login.php";');
        return $response;
    }

    $perfilLogado = strtoupper($uLogado['perfil']);
    $modo         = isset($formData['hdnModoAtual'])  ? $formData['hdnModoAtual']  : 'lista';
    $idUsuario    = isset($formData['hdnIdUsuario'])  ? (int)$formData['hdnIdUsuario'] : 0;

    // regras de permissao replicadas do frontend — proposital
    if ($perfilLogado === 'VISUALIZADOR') {
        $response->script('document.getElementById("btnSalvar").style.display = "none";');
        $response->script('document.getElementById("btnNovo").style.display   = "none";');
        $response->script('var els = document.getElementById("frmUsuario").elements;'
            . ' for(var i=0;i<els.length;i++){ els[i].disabled = true; }');
    }

    if ($perfilLogado === 'OPERADOR') {
        // OPERADOR nao pode alterar perfil
        $response->script('document.getElementById("selPerfil").disabled = true;');
    }

    if ($perfilLogado !== 'ADMIN') {
        // apenas ADMIN pode excluir — mas essa regra so aparece aqui, nao no HTML
        $response->script('var lnks = document.querySelectorAll(".lnk-excluir");'
            . ' for(var i=0;i<lnks.length;i++){ lnks[i].style.display="none"; }');
    }

    // atualiza label de perfil logado no rodape
    $response->assign('spnPerfilLogado', 'innerHTML', $perfilLogado);

    return $response;
}

// ============================================================
// validarEmailAjax  —  xajax: verifica duplicidade de email
// adicionado em 2013 — chama em tempo real ao sair do campo
// ============================================================
function validarEmailAjax($email, $idAtual) {
    $response = new xajaxResponse();

    $email    = trim((string)$email);
    $idAtual  = (int)$idAtual;

    // validacao de formato — regex simples de 2008, nao cobre todos os casos
    if (!strpos($email, '@')) {
        $response->assign('divErroEmail', 'innerHTML',
            '<span style="color:red;font-size:10px;">Email invalido (sem @)</span>');
        return $response;
    }

    // verifica duplicidade no banco
    $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ? AND ativo = 1";
    $st  = getConn()->prepare($sql);
    $st->execute(array($email, $idAtual));
    $existe = (int)$st->fetchColumn();

    if ($existe > 0) {
        $response->assign('divErroEmail', 'innerHTML',
            '<span style="color:red;font-size:10px;font-weight:bold;">Email ja cadastrado!</span>');
        $response->script('document.getElementById("txtEmail").style.border = "2px solid red";');
    } else {
        $response->assign('divErroEmail', 'innerHTML',
            '<span style="color:green;font-size:10px;">&#10003; Email disponivel</span>');
        $response->script('document.getElementById("txtEmail").style.border = "";');
    }

    return $response;
}

// ============================================================
// mudarModoTelaAjax  —  xajax: alterna entre novo/editar/lista
// ============================================================
function mudarModoTelaAjax($modo) {
    $response = new xajaxResponse();

    $modo = trim((string)$modo);

    if ($modo === 'novo') {
        // limpa formulario — campo por campo (sem loop)
        $response->assign('txtNome',      'value', '');
        $response->assign('txtEmail',     'value', '');
        $response->assign('txtLogin',     'value', '');
        $response->assign('txtSenha',     'value', '');
        $response->assign('txtObs',       'value', '');
        $response->assign('hdnIdUsuario', 'value', '0');
        $response->assign('hdnModoAtual', 'value', 'novo');
        $response->script('document.getElementById("selPerfil").value = "OPERADOR";');
        $response->script('document.getElementById("divTituloForm").innerHTML = "NOVO USUARIO";');
        $response->script('document.getElementById("divFormUsuario").style.display = "block";');
        $response->script('document.getElementById("btnSalvar").style.display = "inline";');
        // reabilita campos que podem ter ficado disabled
        $response->script('var els = document.getElementById("frmUsuario").elements;'
            . ' for(var i=0;i<els.length;i++){ els[i].disabled = false; }');
        $response->assign('hdnLogin', 'value', '');
        $response->script('var elLogin = document.getElementById("txtLogin"); elLogin.readOnly = false; elLogin.disabled = false;');
        $response->assign('divBadgePerfil',    'innerHTML', 'Perfil: <b>OPERADOR</b>');
        $response->assign('divAvisoPermissao', 'innerHTML', '');
        $response->assign('divErroEmail',      'innerHTML', '');
        $response->assign('divStatus',         'innerHTML', '');
        $response->script('document.getElementById("divFormUsuario").scrollIntoView();');
    } elseif ($modo === 'lista') {
        $response->script('document.getElementById("divFormUsuario").style.display = "none";');
        $response->assign('divStatus', 'innerHTML', '');
    }

    return $response;
}

// ============================================================
// excluirUsuario  —  xajax: exclusao logica
// ============================================================
function excluirUsuario($id) {
    $response = new xajaxResponse();

    $id      = (int)$id;
    $uLogado = getUsuarioLogado();

    if (!$uLogado) {
        $response->script('window.location = "login.php";');
        return $response;
    }

    // permissao — so ADMIN pode excluir
    if ($uLogado['perfil'] !== 'ADMIN') {
        $response->assign('divStatus', 'innerHTML',
            '<div style="color:red;font-weight:bold;padding:5px;">'
            . 'Sem permissao para excluir. Apenas ADMIN.</div>');
        return $response;
    }

    // nao pode excluir a si mesmo — regra de negocio inline, sem service
    if ($id === (int)$uLogado['id']) {
        $response->assign('divStatus', 'innerHTML',
            '<div style="color:#cc6600;font-weight:bold;padding:5px;">'
            . 'Nao e possivel excluir o proprio usuario.</div>');
        return $response;
    }

    // exclusao logica — UPDATE ativo=0, nunca DELETE
    // "nunca deleta do banco, so inativa" — decisao de 2007 que virou regra
    $st = getConn()->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
    $st->execute(array($id));

    $response->assign('divStatus', 'innerHTML',
        '<div style="color:green;font-weight:bold;padding:5px;">'
        . 'Usuario ' . $id . ' excluido com sucesso.</div>');

    // recarrega lista inline
    $html = montarHtmlListaUsuarios('');
    $response->assign('divUsuarios', 'innerHTML', $html);
    $response->script('document.getElementById("divFormUsuario").style.display = "none";');

    return $response;
}

// ============================================================
// salvarUsuario  —  xajax: insere ou atualiza usuario
//
// !!!  FUNCAO DE 300+ LINHAS — legado puro  !!!
// Historico de modificacoes:
//   2007 — Carlos: versao inicial, so inseria
//   2009 — Marcelo: adicionou edicao, duplicou validacao
//   2011 — Rodrigo: adicionou permissoes, mais ifs aninhados
//   2013 — Thiago: adicionou validacao de email duplicado (inline)
//   2015 — Ana: corrigiu bug de senha, adicionou mais 50 linhas
//   2018 — TI: patch de seguranca, mais validacoes duplicadas
//   2019 — Fabio: "refatoracao" que adicionou mais duplicacao
// ============================================================
function salvarUsuario($formData) {
    $response = new xajaxResponse();

    // --- pega usuario logado ---
    $uLogado = getUsuarioLogado();
    if (!$uLogado) {
        $response->script('window.location = "login.php";');
        return $response;
    }
    $perfilLogado = strtoupper($uLogado['perfil']);

    // --- leitura dos campos do formulario --- (um por um, sem loop — padrao 2007)
    $id           = isset($formData['hdnIdUsuario']) ? (int)$formData['hdnIdUsuario'] : 0;
    $modo         = isset($formData['hdnModoAtual']) ? trim((string)$formData['hdnModoAtual']) : 'novo';
    $nome         = isset($formData['txtNome'])      ? trim((string)$formData['txtNome'])      : '';
    $email        = isset($formData['txtEmail'])     ? trim((string)$formData['txtEmail'])     : '';
    $login        = isset($formData['txtLogin'])     ? trim((string)$formData['txtLogin'])     : '';
    if (empty($login) && !empty($formData['hdnLogin'])) {
        $login = trim((string)$formData['hdnLogin']);
    }
    // fallback: campo disabled/readonly sem valor no POST — busca no banco
    $idTmp = isset($formData['hdnIdUsuario']) ? (int)$formData['hdnIdUsuario'] : 0;
    if (empty($login) && $idTmp > 0) {
        $stLogin = getConn()->prepare("SELECT login FROM usuarios WHERE id = ? AND ativo = 1");
        $stLogin->execute(array($idTmp));
        $login = (string)$stLogin->fetchColumn();
    }
    $senha        = isset($formData['txtSenha'])     ? trim((string)$formData['txtSenha'])     : '';
    $perfil       = isset($formData['selPerfil'])    ? strtoupper(trim((string)$formData['selPerfil'])) : 'OPERADOR';
    $obs          = isset($formData['txtObs'])       ? trim((string)$formData['txtObs'])       : '';

    // variaveis de controle — globais por costume antigo
    $erros        = array();
    $avisos       = array(); // adicionado Thiago 2013, nunca chegou a ser usado de verdade
    $operacao     = ($id > 0) ? 'EDICAO' : 'INSERCAO'; // nome em maiusculo pra log (que nao existe)

    // ================================================================
    // BLOCO 1: VALIDACOES OBRIGATORIAS
    // duplicadas do JavaScript — "tem que validar dos dois lados" -- Carlos 2007
    // ================================================================

    // valida nome
    if (empty($nome)) {
        $erros[] = 'Nome e obrigatorio';
    }
    if (strlen($nome) < 3) {
        // validacao adicional — Rodrigo 2011
        if (!empty($nome)) { // if aninhado desnecessario
            $erros[] = 'Nome deve ter pelo menos 3 caracteres';
        }
    }
    if (strlen($nome) > 100) {
        $erros[] = 'Nome nao pode ter mais de 100 caracteres';
    }
    // validacao de nome duplicada de outro jeito — adicionada em 2018 pelo TI
    $nomeTrimmed = strip_tags(trim($nome));
    if (empty($nomeTrimmed)) {
        $erros[] = 'Nome nao pode ser vazio (verificacao extra seguranca)';
    }

    // valida email
    if (empty($email)) {
        $erros[] = 'Email e obrigatorio';
    }
    if (!empty($email) && !strpos($email, '@')) {
        // validacao basica de 2007 — incompleta mas nunca atualizada
        $erros[] = 'Email invalido';
    }
    // segunda validacao de email — adicionada por Ana 2015 sem remover a primeira
    if (!empty($email) && strlen($email) > 150) {
        $erros[] = 'Email muito longo';
    }
    // terceira verificacao — Fabio 2019 "reforco de seguranca"
    if (!empty($email)) {
        $partes = explode('@', $email);
        if (count($partes) != 2) {
            $erros[] = 'Formato de email invalido';
        }
        // TODO: validar dominio tambem -- Fabio 2019 (nunca fez)
    }

    // valida login
    if (empty($login)) {
        $erros[] = 'Login e obrigatorio';
    }
    if (strlen($login) < 3) {
        if (!empty($login)) {
            $erros[] = 'Login deve ter pelo menos 3 caracteres';
        }
    }
    if (strlen($login) > 30) {
        $erros[] = 'Login nao pode ter mais de 30 caracteres';
    }
    // validacao de caracteres — adicionada 2015 pois tinha usuario com espacos
    if (!empty($login) && preg_match('/\s/', $login)) {
        $erros[] = 'Login nao pode conter espacos';
    }

    // valida senha — so obrigatoria em insercao
    if ($operacao === 'INSERCAO') {
        if (empty($senha)) {
            $erros[] = 'Senha e obrigatoria para novo usuario';
        }
        if (strlen($senha) < 4) {
            if (!empty($senha)) {
                $erros[] = 'Senha deve ter pelo menos 4 caracteres';
            }
        }
        // validacao adicional de senha — adicionada por Ana 2015
        // "cliente pediu senha forte mas so temos isso por enquanto"
        if (!empty($senha) && strlen($senha) < 6) {
            // isso vai repetir o erro acima pra senhas de 4-5 chars — bug proposital
            $avisos[] = 'Recomendado: senha com 6 ou mais caracteres';
        }
    } else {
        // edicao: senha opcional — se vier em branco, mantem a antiga
        // comentario original de Marcelo 2009:
        // "se senha vazia nao atualiza — IMPORTANTE nao tirar esse if"
        if (!empty($senha) && strlen($senha) < 4) {
            $erros[] = 'Nova senha deve ter pelo menos 4 caracteres';
        }
    }

    // valida perfil — lista hardcoded igual ao JS e ao define no bootstrap
    $perfisValidos = array('ADMIN', 'SUPERVISOR', 'OPERADOR', 'VISUALIZADOR');
    if (!in_array($perfil, $perfisValidos)) {
        $erros[] = 'Perfil invalido: ' . htmlspecialchars($perfil);
    }

    // ================================================================
    // BLOCO 2: VERIFICACOES DE PERMISSAO
    // logica duplicada: tem no JS, aqui e no bootstrap (verificaPermissao)
    // "precisa validar no servidor tambem" — Carlos 2009
    // ================================================================

    // VISUALIZADOR nao pode salvar
    if ($perfilLogado === 'VISUALIZADOR') {
        $erros[] = 'Sem permissao para salvar (perfil VISUALIZADOR)';
    }

    // OPERADOR nao pode definir ADMIN ou SUPERVISOR
    if ($perfilLogado === 'OPERADOR') {
        if ($perfil === 'ADMIN' || $perfil === 'SUPERVISOR') {
            $erros[] = 'Operador nao pode atribuir perfil ' . $perfil;
        }
    }

    // nao pode alterar o proprio perfil — regra de 2011
    if ($id > 0 && $id === (int)$uLogado['id']) {
        $perfilOriginal = isset($formData['hdnPerfilOriginal']) ? $formData['hdnPerfilOriginal'] : '';
        if ($perfil !== $perfilOriginal && !empty($perfilOriginal)) {
            $erros[] = 'Nao e permitido alterar o proprio perfil';
        }
    }

    // ================================================================
    // BLOCO 3: SE TEM ERROS, EXIBE E PARA
    // HTML montado por concatenacao — padrao empresa
    // ================================================================

    if (!empty($erros)) {
        $htmlErros  = '<div style="background:#ffe6e6;border:1px solid #cc0000;padding:8px;margin:5px 0;">';
        $htmlErros .= '<b style="color:#cc0000;">Erros encontrados:</b><ul style="margin:4px 0;padding-left:20px;">';
        foreach ($erros as $e) {
            $htmlErros .= '<li style="color:#cc0000;font-size:11px;">' . htmlspecialchars($e) . '</li>';
        }
        $htmlErros .= '</ul></div>';

        $response->assign('divStatus', 'innerHTML', $htmlErros);
        // pisca o div de erros — JS inline gerado pelo backend
        $response->script('document.getElementById("divStatus").scrollIntoView();');
        return $response;
    }

    // ================================================================
    // BLOCO 4: VERIFICACOES NO BANCO (duplicidade)
    // feitas DEPOIS das validacoes basicas — ordem questionavel
    // ================================================================

    $conn = getConn();

    // verifica email duplicado — SQL inline, sem repository
    if ($operacao === 'INSERCAO') {
        $stChkEmail = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND ativo = 1");
        $stChkEmail->execute(array($email));
        if ((int)$stChkEmail->fetchColumn() > 0) {
            $response->assign('divStatus', 'innerHTML',
                '<div style="background:#ffe6e6;border:1px solid #cc0000;padding:8px;">'
                . '<b style="color:red;">Email ja esta em uso por outro usuario!</b></div>');
            $response->script('document.getElementById("txtEmail").focus();');
            $response->script('document.getElementById("txtEmail").style.border = "2px solid red";');
            return $response;
        }
    } else {
        // edicao: verifica email de outro usuario
        $stChkEmail = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ? AND ativo = 1");
        $stChkEmail->execute(array($email, $id));
        if ((int)$stChkEmail->fetchColumn() > 0) {
            $response->assign('divStatus', 'innerHTML',
                '<div style="background:#ffe6e6;border:1px solid #cc0000;padding:8px;">'
                . '<b style="color:red;">Email ja esta em uso por outro usuario!</b></div>');
            return $response;
        }
    }

    // verifica login duplicado — so na insercao (nao permite alterar login)
    if ($operacao === 'INSERCAO') {
        $stChkLogin = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE login = ? AND ativo = 1");
        $stChkLogin->execute(array($login));
        if ((int)$stChkLogin->fetchColumn() > 0) {
            $response->assign('divStatus', 'innerHTML',
                '<div style="background:#ffe6e6;border:1px solid #cc0000;padding:8px;">'
                . '<b style="color:red;">Login ja esta em uso. Escolha outro.</b></div>');
            $response->script('document.getElementById("txtLogin").focus();');
            return $response;
        }
    }

    // ================================================================
    // BLOCO 5: PERSISTENCIA NO BANCO
    // SQL inline, sem ORM, sem repository — puro legado
    // ================================================================

    if ($operacao === 'INSERCAO') {
        // insere novo usuario
        // "md5 da senha — sei que e fraco mas sistema antigo, nao da pra mudar" -- Carlos 2009
        $senhaMd5 = md5($senha);

        // SQL montado por concatenacao — anti-padrao total
        $sqlInsert  = "INSERT INTO usuarios (nome, email, perfil, login, senha, ativo, obs, dt_cadastro) ";
        $sqlInsert .= "VALUES (?, ?, ?, ?, ?, 1, ?, datetime('now','localtime'))";

        $stInsert = $conn->prepare($sqlInsert);
        $stInsert->execute(array($nome, $email, $perfil, $login, $senhaMd5, $obs));

        $novoId = $conn->lastInsertId();

        // log fake — variavel nunca usada, mas "fica ai pra quando tiver log de verdade"
        $logMsg = "[" . date('Y-m-d H:i:s') . "] INSERCAO: id=$novoId login=$login por {$uLogado['login']}";
        // file_put_contents('/var/log/sistema.log', $logMsg . "\n", FILE_APPEND); // desativado 2015

        $response->assign('divStatus', 'innerHTML',
            '<div style="background:#e6ffe6;border:1px solid #009900;padding:8px;">'
            . '<b style="color:green;">Usuario <u>' . htmlspecialchars($nome) . '</u> cadastrado com sucesso!'
            . ' (ID: ' . $novoId . ')</b></div>');

        // limpa formulario apos insercao
        $response->assign('txtNome',      'value', '');
        $response->assign('txtEmail',     'value', '');
        $response->assign('txtLogin',     'value', '');
        $response->assign('txtSenha',     'value', '');
        $response->assign('txtObs',       'value', '');
        $response->assign('hdnIdUsuario', 'value', '0');
        $response->script('document.getElementById("selPerfil").value = "OPERADOR";');

    } else {
        // edicao de usuario existente
        // verifica se usuario ainda existe antes de atualizar — verificacao redundante
        $stChkExist = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ? AND ativo = 1");
        $stChkExist->execute(array($id));
        if ((int)$stChkExist->fetchColumn() === 0) {
            $response->assign('divStatus', 'innerHTML',
                '<div style="color:red;font-weight:bold;padding:8px;">'
                . 'ERRO: Usuario nao encontrado no banco (id=' . $id . ')</div>');
            return $response;
        }

        // monta SQL de update — sem senha se vier vazia
        if (!empty($senha)) {
            $senhaMd5  = md5($senha);
            $sqlUpdate = "UPDATE usuarios SET nome=?, email=?, perfil=?, senha=?, obs=? WHERE id=?";
            $params    = array($nome, $email, $perfil, $senhaMd5, $obs, $id);
        } else {
            // nao atualiza senha se campo vier vazio
            $sqlUpdate = "UPDATE usuarios SET nome=?, email=?, perfil=?, obs=? WHERE id=?";
            $params    = array($nome, $email, $perfil, $obs, $id);
        }

        $stUpdate = $conn->prepare($sqlUpdate);
        $stUpdate->execute($params);

        // log fake igual ao de insercao — copia e cola do bloco acima (duplicacao intencional)
        $logMsg = "[" . date('Y-m-d H:i:s') . "] EDICAO: id=$id login=$login por {$uLogado['login']}";
        // file_put_contents('/var/log/sistema.log', $logMsg . "\n", FILE_APPEND); // desativado

        $response->assign('divStatus', 'innerHTML',
            '<div style="background:#e6ffe6;border:1px solid #009900;padding:8px;">'
            . '<b style="color:green;">Usuario <u>' . htmlspecialchars($nome) . '</u> atualizado com sucesso!</b></div>');
    }

    // ================================================================
    // BLOCO 6: POS-SALVAMENTO — atualiza tela
    // multiples assigns e scripts inline — acoplamento maximo
    // ================================================================

    // recarrega lista de usuarios
    $htmlLista = montarHtmlListaUsuarios('');
    $response->assign('divUsuarios', 'innerHTML', $htmlLista);

    // atualiza badge de perfil
    $response->script('atualizarBadgePerfil("' . $perfil . '");');

    // scroll pro status
    $response->script('document.getElementById("divStatus").scrollIntoView();');

    // se era insercao, esconde o form (volta pra lista)
    if ($operacao === 'INSERCAO') {
        $response->script('setTimeout(function(){'
            . ' document.getElementById("divFormUsuario").style.display="none";'
            . '}, 1500);');
    }

    // limpa bordas de erro que podem ter ficado do validarEmailAjax
    $response->script('document.getElementById("txtEmail").style.border = "";');
    $response->assign('divErroEmail',      'innerHTML', '');
    $response->assign('divAvisoPermissao', 'innerHTML', '');

    return $response;
}
