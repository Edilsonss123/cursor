<?php
/**
 * logout.php — encerra sessao e volta pro login
 * Criado 2026: antes logout ficava no login.php DEPOIS do redirect (bug loop index)
 */
require_once __DIR__ . '/system/bootstrap.php';

fazerLogout();

header('Location: login.php?msg=logout');
exit;
