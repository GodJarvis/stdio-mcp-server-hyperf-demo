#!/usr/bin/env php
<?php

declare(strict_types=1);

use Hyperf\Contract\ApplicationInterface;
use Hyperf\Di\ClassLoader;
use Hyperf\Engine\DefaultOption;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\StreamOutput;

ini_set('display_errors', 'stderr');
ini_set('display_startup_errors', 'stderr');
ini_set('memory_limit', '1G');
error_reporting(E_ALL);

defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

defined('SWOOLE_HOOK_FLAGS') || define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());

(static function (): void {
    ClassLoader::init();
    /** @var ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';
    $input = new ArgvInput();
    $output = $input->getFirstArgument() === 'mcp:serve'
        ? new StreamOutput(STDERR, decorated: false)
        : null;
    $container->get(ApplicationInterface::class)->run($input, $output);
})();
