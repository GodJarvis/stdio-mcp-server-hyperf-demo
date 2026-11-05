<?php

declare(strict_types=1);

namespace App\Factory;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Framework\Logger\StdoutLogger;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\StreamOutput;

final class StderrLoggerFactory
{
    public function __invoke(ContainerInterface $container): StdoutLogger
    {
        return new StdoutLogger(
            $container->get(ConfigInterface::class),
            new StreamOutput(STDERR, decorated: false),
        );
    }
}
