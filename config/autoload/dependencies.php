<?php

declare(strict_types=1);

return [
    // Reserve stdout for MCP responses, including logs from lifecycle listeners.
    Hyperf\Contract\StdoutLoggerInterface::class => App\Factory\StderrLoggerFactory::class,
];
