<?php

declare(strict_types=1);

namespace App\Mcp;

use RuntimeException;

final class StdioServer
{
    public function __construct(private readonly ProtocolHandler $handler)
    {
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function run($input, $output): void
    {
        while (($line = fgets($input)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $response = $this->handler->handleJson($line);
            if ($response === null) {
                continue;
            }

            $payload = $response . "\n";
            $length = strlen($payload);
            $offset = 0;
            while ($offset < $length) {
                $written = fwrite($output, substr($payload, $offset));
                if ($written === false || $written === 0) {
                    throw new RuntimeException('Unable to write MCP response.');
                }
                $offset += $written;
            }

            if (! fflush($output)) {
                throw new RuntimeException('Unable to flush MCP response.');
            }
        }

        if (! feof($input)) {
            throw new RuntimeException('Unable to read MCP request.');
        }
    }
}
