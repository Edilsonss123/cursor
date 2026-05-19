<?php
set_exception_handler(function ($th) {
    saveLog($th->getMessage(), [
        'file' => $th->getFile(),
        'line' => $th->getLine(),
        'trace' => $th->getTraceAsString(),
    ]);
    echo response("Operation failed, please try again later", [], 500);
    exit;
});
