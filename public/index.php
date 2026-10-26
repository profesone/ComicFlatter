<?php

declare(strict_types=1);

use App\Http\Controllers;
use App\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($path, '/uploads/')) {
    Controllers::upload([]);
    exit;
}

$router = new Router();
$router->get('/', [Controllers::class, 'home']);
$router->get('/flatter', [Controllers::class, 'flatter']);
$router->get('/balloons', [Controllers::class, 'balloons']);
$router->post('/balloons/upload', [Controllers::class, 'balloonUpload']);
$router->post('/balloons/generate', [Controllers::class, 'balloonGenerate']);
$router->get('/jobs', [Controllers::class, 'jobs']);
$router->post('/jobs', [Controllers::class, 'jobCreate']);
$router->get('/privacy', [Controllers::class, 'privacy']);
$router->get('/terms', [Controllers::class, 'terms']);
$router->get('/conduct', [Controllers::class, 'conduct']);
$router->get('/login', [Controllers::class, 'loginForm']);
$router->post('/login', [Controllers::class, 'login']);
$router->get('/register', [Controllers::class, 'registerForm']);
$router->post('/register', [Controllers::class, 'register']);
$router->post('/logout', [Controllers::class, 'logout']);
$router->get('/forgot', [Controllers::class, 'forgotForm']);
$router->post('/forgot', [Controllers::class, 'forgot']);
$router->get('/reset', [Controllers::class, 'resetForm']);
$router->post('/reset', [Controllers::class, 'reset']);
$router->get('/account', [Controllers::class, 'account']);
$router->post('/account', [Controllers::class, 'accountUpdate']);
$router->post('/account/delete', [Controllers::class, 'accountDelete']);
$router->get('/studio', [Controllers::class, 'studio']);
$router->post('/checkout', [Controllers::class, 'checkout']);
$router->get('/checkout/success', [Controllers::class, 'checkoutSuccess']);
$router->post('/webhook/stripe', [Controllers::class, 'webhook']);
$router->post('/studio/process', [Controllers::class, 'process']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'HEAD') {
    $method = 'GET';
}
$router->dispatch($method, $_SERVER['REQUEST_URI'] ?? '/');
