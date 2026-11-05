#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Mcp\ProtocolHandler;
use Hyperf\Di\ClassLoader;
use Psr\Container\ContainerInterface;

ini_set('display_errors', 'stderr');
ini_set('display_startup_errors', 'stderr');
ini_set('memory_limit', '256M');
error_reporting(E_ALL);

defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

set_exception_handler(static function (\Throwable $throwable): void {
    fwrite(STDERR, sprintf("Unhandled server error: %s\n", $throwable->getMessage()));
    exit(1);
});

(static function (): void {
    ClassLoader::init();
    /** @var ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';
    $handler = $container->get(ProtocolHandler::class);

    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $response = $handler->handleJson($line);
        if ($response === null) {
            continue;
        }

        fwrite(STDOUT, $response . "\n");
        fflush(STDOUT);
    }
})();
