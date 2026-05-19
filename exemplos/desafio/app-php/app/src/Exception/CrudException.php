<?php

namespace App\Exception;

class CrudException extends \Exception {
    private $additionalInfo;

    public function __construct($message, $code = 0, \Exception $previous = null, $additionalInfo = null) {
        $this->additionalInfo = $additionalInfo;
        parent::__construct($message, $code, $previous);
    }

    public function getAdditionalInfo() {
        return $this->additionalInfo;
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n" . ($this->additionalInfo ? "Info adicional: {$this->additionalInfo}\n" : "");
    }
}