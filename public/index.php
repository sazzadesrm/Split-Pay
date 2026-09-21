<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

/** @var Router $router */
$router = require dirname(__DIR__) . '/routes/web.php';

$request = new Request();
$router->dispatch($request);
