<?php
use Lib\Router\Route;
use Lib\Router\Dispatcher;

Route::get('peoples', 'App\Controller\PeopleController@getAll');
Route::get('people', 'App\Controller\PeopleController@getFindById');
Route::post('people', 'App\Controller\PeopleController@create');
Route::put('people', 'App\Controller\PeopleController@update');
Route::delete('people', 'App\Controller\PeopleController@delete');

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? "";
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? "", PHP_URL_PATH);
$dispatcher = new Dispatcher($requestMethod, $requestUri);
$dispatcher->dispatch();