<?php
namespace Lib\Router;

use App\Exception\CrudException;
use App\DB\Database;

class Dispatcher {
    private $requestMethod;
    private $requestUri;

    public function __construct($requestMethod, $requestUri) {
		$this->requestMethod = $requestMethod;
		$this->requestUri = $requestUri;
    }

    public function dispatch() {
		cors();
		if (!empty(Route::$routes[$this->requestMethod][$this->requestUri])) {
			$action = Route::$routes[$this->requestMethod][$this->requestUri];
			$this->executeAction($action);
		}
		echo response("404 Not Found", [], 404);
		exit();
    }

	private function executeAction($action) : void
	{
		$database = Database::getInstance();
		$database->exec('BEGIN TRANSACTION');
	
		$response = null;
	
		try {
			if (is_callable($action)) {
				$response = $action();
			} else {
				list($controller, $method) = explode('@', $action);
				$controllerInstance = new $controller;
				$response = $controllerInstance->$method();
			}
	
			$database->exec('COMMIT');
		} catch (CrudException $th) {
			$database->exec('ROLLBACK');
			$response = response($th->getMessage(), $th->getAdditionalInfo() ?: [], $th->getCode());
		} catch (\Throwable $th) {
			$database->exec('ROLLBACK');
			saveLog($th->getMessage(), [
				'file' => $th->getFile(),
				'line' => $th->getLine(),
				'trace' => $th->getTraceAsString(),
			]);
			$response = response("Operation failed, please try again later", [], 500);
		} finally {
			echo $response;
			exit;
		}
	}
	
}
