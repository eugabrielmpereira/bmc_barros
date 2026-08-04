<?php

declare(strict_types=1);

use Clients\Demo\Site\Controllers\CartController;
use Clients\Demo\Site\Controllers\CatalogController;
use Clients\Demo\Site\Controllers\CustomerAccountController;
use Clients\Demo\Site\Controllers\HomeController;
use Clients\Demo\Site\Controllers\OrderTrackingController;

return [
    ['method' => 'GET', 'path' => '/', 'handler' => [HomeController::class, 'index'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/entrar', 'handler' => [CustomerAccountController::class, 'loginForm'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/entrar', 'handler' => [CustomerAccountController::class, 'login'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/cadastro', 'handler' => [CustomerAccountController::class, 'registerForm'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/cadastro', 'handler' => [CustomerAccountController::class, 'register'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/sair', 'handler' => [CustomerAccountController::class, 'logout'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/meus-pedidos', 'handler' => [CustomerAccountController::class, 'orders'], 'middleware' => 'customer'],
    ['method' => 'GET', 'path' => '/meus-pedidos/:reference', 'handler' => [CustomerAccountController::class, 'orderShow'], 'middleware' => 'customer'],
    ['method' => 'GET', 'path' => '/minha-conta', 'handler' => [CustomerAccountController::class, 'profile'], 'middleware' => 'customer'],
    ['method' => 'POST', 'path' => '/minha-conta', 'handler' => [CustomerAccountController::class, 'updateProfile'], 'middleware' => 'customer'],
    ['method' => 'GET', 'path' => '/minha-conta/enderecos', 'handler' => [CustomerAccountController::class, 'addresses'], 'middleware' => 'customer'],
    ['method' => 'POST', 'path' => '/minha-conta/enderecos', 'handler' => [CustomerAccountController::class, 'saveAddress'], 'middleware' => 'customer'],
    ['method' => 'POST', 'path' => '/minha-conta/enderecos/:id/remover', 'handler' => [CustomerAccountController::class, 'deleteAddress'], 'middleware' => 'customer'],
    ['method' => 'POST', 'path' => '/minha-conta/enderecos/:id/padrao', 'handler' => [CustomerAccountController::class, 'setDefaultAddress'], 'middleware' => 'customer'],
    ['method' => 'GET', 'path' => '/carrinho', 'handler' => [CartController::class, 'index'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/carrinho/resumo', 'handler' => [CartController::class, 'summary'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/carrinho/adicionar', 'handler' => [CartController::class, 'add'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/produtos/avisar-estoque', 'handler' => [CartController::class, 'subscribeStockAlert'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/carrinho/atualizar', 'handler' => [CartController::class, 'update'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/carrinho/remover', 'handler' => [CartController::class, 'remove'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/cep-consulta', 'handler' => [CartController::class, 'lookupPostalCode'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/checkout', 'handler' => [CartController::class, 'checkout'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/checkout', 'handler' => [CartController::class, 'placeOrder'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/pedido-recebido', 'handler' => [CartController::class, 'orderReceived'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/acompanhar-pedido', 'handler' => [OrderTrackingController::class, 'index'], 'middleware' => 'web'],
    ['method' => 'POST', 'path' => '/acompanhar-pedido', 'handler' => [OrderTrackingController::class, 'lookup'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/produtos', 'handler' => [CatalogController::class, 'index'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/produtos/:slug', 'handler' => [CatalogController::class, 'show'], 'middleware' => 'web'],
];
