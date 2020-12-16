<?php

declare(strict_types=1);

use HyperfPlus\Route\Route;
use Hyperf\Tracer\Middleware\TraceMiddleware;
use HyperfPlus\Middleware\CorsMiddleware;

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'HyperfPlus\Controller\IndexController@handle');

Router::get('/favicon.ico', function () {
    return '';
});

Router::addGroup('/v1/',function () {
    /**
     * 文章模块
     */
    Router::addRoute(['GET'], 'article/search', Route::decoration('Article\Action\SearchAction'));
    Router::addRoute(['GET'], 'article/find', Route::decoration('Article\Action\FindAction'));
    Router::addRoute(['POST'], 'article/create', Route::decoration('Article\Action\CreateAction'));
    Router::addRoute(['POST'], 'article/update', Route::decoration('Article\Action\UpdateAction'));
    Router::addRoute(['POST'], 'article/update_field', Route::decoration('Article\Action\UpdateFieldAction'));
}, ['middleware' => [TraceMiddleware::class, CorsMiddleware::class]]);