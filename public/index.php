<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
} else {
    // Fallback autoloader for environments without Composer
    spl_autoload_register(static function (string $class): void {
        if (strpos($class, 'App\\') !== 0) {
            return;
        }
        $relative = substr($class, 4);
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

use App\Router;
use App\Controllers\TraceController;

// Simple bootstrap
$router = new Router();

// Only keep link trace detection
$router->get('/', [TraceController::class, 'form']);

// Link trace detection
$router->get('/trace', [TraceController::class, 'form']);
$router->post('/trace', [TraceController::class, 'start']);
$router->get('/img', [TraceController::class, 'image']);
$router->get('/trace-logs', [TraceController::class, 'logs']);

$routeParam = isset($_GET['r']) ? (string)$_GET['r'] : '';
if ($routeParam !== '') {
    $path = '/' . ltrim((string)parse_url($routeParam, PHP_URL_PATH), '/');
} else {
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string)(parse_url($requestUri, PHP_URL_PATH) ?: '/');
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);


