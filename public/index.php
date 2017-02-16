<?php
require __DIR__ . '../../vendor/autoload.php';


$config = require __DIR__ . '../../config/env/dev.php';
$dependencies = require __DIR__ . '../../app/dependencies.php';
$middleware = require __DIR__ . '../../app/middleware.php';
$routes = require __DIR__ . '../../app/routes.php';

$app = new \App\App();
$app->run($config, $dependencies, $middleware, $routes);