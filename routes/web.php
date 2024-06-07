<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('count-data', 'EPRS\PurchaseOrdersController@countRow');
    $router->get('export-data', 'EPRS\PurchaseOrdersController@exportDataToJSON');
    $router->get('refresh-data', 'EPRS\PurchaseOrdersController@refresh');


    $router->get('monitoring', 'EPRS\RequisitionsController@getData');
    $router->get('monitoring/countData', 'EPRS\RequisitionsController@countData');
    $router->get('monitoring/exportData', 'EPRS\RequisitionsController@exportData');
    $router->get('monitoring/detail', 'EPRS\RequisitionsController@getDetailData');
    $router->get('monitoring/detail/exportData', 'EPRS\RequisitionsController@exportDetail');
    $router->get('monitoring/search', 'EPRS\RequisitionsController@searchData');

    $router->get('aging', 'AccApp\InvoicesController@getData');
    $router->get('aging/{code}', 'AccApp\InvoicesController@getDetail');
});
