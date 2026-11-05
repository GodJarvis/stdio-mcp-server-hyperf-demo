# Hyperf stdio MCP Server Demo

这是一个运行于 PHP 8.5、基于 Hyperf 3.2 发布线的标准 stdio MCP Server。项目只暴露一个 `calculate_discount` 工具，用于计算折扣、优惠金额和最终应付金额。

> Hyperf `v3.2.4` 是整体仓库 Release；Hyperf 采用拆分组件发布，`hyperf/framework` 组件当前稳定版本仍是 `v3.2.0`。本项目使用 `~3.2.0` 约束并由 `composer.lock` 锁定该 Release 对应的实际组件版本。

## 启动

```bash
composer install
php bin/mcp-server.php
```

stdio 传输要求：客户端每次向 stdin 写入一行 JSON-RPC，服务端每次向 stdout 写回一行 JSON-RPC。stdout 不会混入日志；未捕获错误只写 stderr。

## 客户端配置

```json
{
  "mcpServers": {
    "discount-stdio": {
      "command": "php",
      "args": [
        "/home/zw/vibe-demo/stdio-mcp-server-demo/bin/mcp-server.php"
      ]
    }
  }
}
```

## 手工验证（兼容旧版初始化流程）

```bash
printf '%s\n' \
  '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"demo","version":"1.0.0"}}}' \
  '{"jsonrpc":"2.0","method":"notifications/initialized"}' \
  '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' \
  '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"calculate_discount","arguments":{"original_price":100,"discount_percent":20,"quantity":3}}}' \
  | php bin/mcp-server.php
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
