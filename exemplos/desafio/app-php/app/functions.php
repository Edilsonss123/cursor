<?php
if (!function_exists('dd')) {
    function dd(...$vars) {
        foreach ($vars as $var) {
            var_dump($var);
            echo "<br>";
        }

        die();
    }
}


function response($message, $data = [], $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    return json_encode([
        "message" => $message,
        "result" => $data,
    ]);
}


function saveLog(string $message, array $details, $filePath = 'logs/app.log') {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message";
    if (!empty($details)) {
        $logMessage .= PHP_EOL."details: ". json_encode($details);
    } 
    $logMessage .=  PHP_EOL.PHP_EOL;

    if (!file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    file_put_contents($filePath, $logMessage, FILE_APPEND);
}

function validateCPF($cpf) {
    // Remover qualquer caractere não numérico
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Verificar se o CPF tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Impede CPFs com todos os dígitos iguais (ex.: 111.111.111-11, 222.222.222-22, etc.)
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Validação do primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Validação do segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;

    // Verifica se os dígitos verificadores calculados são iguais aos informados no CPF
    return $cpf[9] == $digito1 && $cpf[10] == $digito2;
}


function getBodyRequest()
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];
    $dataRequest = $_REQUEST ?: [];
    $data = array_merge($data, $dataRequest);
    return $data;
}

function cors()
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Authorization, Accept, Accept-Encoding");
    header("Access-Control-Max-Age: 86400");
    if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}