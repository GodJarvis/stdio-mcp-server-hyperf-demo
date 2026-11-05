# Hyperf stdio MCP Server Demo

这是一个运行于 PHP 8.5、基于 Hyperf 3.2 发布线的标准 stdio MCP Server。项目只暴露一个 `calculate_discount` 工具，用于计算折扣、优惠金额和最终应付金额。

> Hyperf `v3.2.4` 是整体仓库 Release；Hyperf 采用拆分组件发布，`hyperf/framework` 组件当前稳定版本仍是 `v3.2.0`。本项目使用 `~3.2.0` 约束并由 `composer.lock` 锁定该 Release 对应的实际组件版本。

## 启动

```bash
composer install
php bin/hyperf.php mcp:serve
```

启动链：`bin/hyperf.php` → DI 容器 → `ApplicationInterface` / `BootApplication` → `McpServeCommand` → `StdioServer` → `ProtocolHandler`。这样可以复用 Hyperf 的命令注册和 Command 生命周期。

`McpServeCommand` 只负责启动服务；`StdioServer` 处理 stdin/stdout；`ProtocolHandler` 处理协议；`DiscountTool` 处理业务计算。当前传输循环同步运行，命令设置 `$coroutine = false`；后续工具需要协程 API 时，再配合实际 stdin 读写行为评估启用协程。

stdio 传输要求：客户端每次向 stdin 写入一行 JSON-RPC，服务端每次向 stdout 写回一行 JSON-RPC。入口把 `mcp:serve` 的控制台输出定向到 stderr，默认 `StdoutLoggerInterface` 也通过工厂写入 stderr。业务中不要使用 `echo` / `var_dump`，新增日志处理器时同样不能写 stdout。

直接连接 MCP 时请使用下面的 PHP 命令，避免 Composer 自身的输出干扰协议。

## 客户端配置

```json
{
  "mcpServers": {
    "discount-stdio": {
      "command": "php",
      "args": [
        "/home/zw/vibe-demo/stdio-mcp-server-demo/bin/hyperf.php",
        "mcp:serve"
      ]
    }
  }
}
```

宿主机没有 PHP、仅容器中安装了 PHP 时，可以让客户端执行 `docker exec -i`：

```json
{
  "mcpServers": {
    "discount-stdio": {
      "command": "docker",
      "args": [
        "exec",
        "-i",
        "php85-hyperf",
        "php",
        "/var/project/vibe-demo/stdio-mcp-server-demo/bin/hyperf.php",
        "mcp:serve"
      ]
    }
  }
}
```

这里使用当前环境的容器名和挂载路径；迁移环境时需要对应调整。`-i` 保持 stdin 连接，不要添加 `-t`。容器需要提前启动，每次客户端连接会启动独立的命令进程。

## 手工验证（兼容旧版初始化流程）

```bash
printf '%s\n' \
  '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"demo","version":"1.0.0"}}}' \
  '{"jsonrpc":"2.0","method":"notifications/initialized"}' \
  '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' \
  '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"calculate_discount","arguments":{"original_price":100,"discount_percent":20,"quantity":3}}}' \
  | php bin/hyperf.php mcp:serve
```

在当前容器环境中，把管道末尾替换为：

```bash
docker exec -i php85-hyperf php \
  /var/project/vibe-demo/stdio-mcp-server-demo/bin/hyperf.php mcp:serve
```

现代 `2026-07-28` 请求不再依赖 `initialize`，每次请求需要在 `params._meta` 中携带协议版本和客户端能力。服务端同时实现了 `server/discover`。

## 工具参数

| 参数 | 类型 | 必填 | 说明 |
|---|---:|---:|---|
| `original_price` | number | 是 | 原始单价，不能小于 0 |
| `discount_percent` | number | 是 | 优惠百分比，范围 0～100 |
| `quantity` | integer | 否 | 数量，默认 1 |
| `currency` | string | 否 | 三位大写货币代码，默认 `CNY` |
| `precision` | integer | 否 | 小数位数，范围 0～4，默认 2 |

金额按“先舍入单件折后价，再乘数量”的规则计算，避免调用方对舍入顺序产生歧义。

## 测试

```bash
composer test
```

没有本地 PHP 时：

```bash
docker exec -w /var/project/vibe-demo/stdio-mcp-server-demo \
  php85-hyperf composer test
```
