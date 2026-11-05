<?php

declare(strict_types=1);

namespace App\Command;

use App\Mcp\StdioServer;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;

#[Command]
final class McpServeCommand extends HyperfCommand
{
    protected string $description = 'Run the MCP server over stdin/stdout.';

    // The transport reads stdin synchronously and currently needs no coroutine APIs.
    protected bool $coroutine = false;

    public function __construct(private readonly StdioServer $server)
    {
        parent::__construct('mcp:serve');
    }

    public function handle(): int
    {
        $this->server->run(STDIN, STDOUT);

        return self::SUCCESS;
    }
}
