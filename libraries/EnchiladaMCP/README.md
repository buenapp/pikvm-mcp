# EnchiladaMCP

Transport-agnostic Model Context Protocol (MCP) server core: JSON-RPC 2.0 protocol handling, tool/resource/prompt registration via PHP 8 attributes, and the small pieces an Enchilada-based MCP server app wires together.

Vendored into a consuming app at `libraries/EnchiladaMCP/` (namespace `EnchiladaMCP\`). Transport (stdio, reactor I/O) lives separately in `Enchilada\Tortilla` — nothing here reads or writes a socket; `McpServer::handleRequest()` takes a decoded JSON-RPC request array and returns a response array.

## Files

| File | Purpose |
|---|---|
| `McpServer.class.php` | Protocol core: `initialize`, `tools/list`, `tools/call`, `resources/*`, `prompts/*`, `completion/complete`, `ping`. |
| `ToolRegistry.class.php` | Reflects a handler object's public methods for `#[McpTool]`/`#[McpResource]`/`#[McpPrompt]`/`#[McpComplete]` attributes and wires them into the server. One object can expose several tools. |
| `McpTool.class.php` | Attribute marking a method as a callable tool (name, description, input/output schema, `readOnlyHint`/`destructiveHint`/`idempotentHint`/`openWorldHint`, etc. — see the class docblock). |
| `McpResource.class.php`, `McpPrompt.class.php`, `McpComplete.class.php` | Same idea for MCP resources, prompts, and argument-completion providers. |
| `ToolResult.class.php` | Typed return value for tool methods (text/structured content). |
| `ToolWarningInterface.class.php` | Marker interface: exceptions implementing it are returned to the client as a tool error, not a transport-level failure. |
| `ElicitationRequired.class.php` | Thrown by a tool handler that needs a user confirmation mid-call. |
| `InstanceRegistry.class.php` | Base class for servers managing multiple named backend instances (e.g. several Phorge or vCenter endpoints from one config file). |
| `Logger.class.php` | Minimal, failure-proof file logger. Never throws — logging must not be able to take a stdio transport down. |
| `mcp.inc.php` | Tool-class discovery, loading, and registration. Not a class — see below. |

## Writing a tool class

A tool class is a plain object; any public method carrying `#[McpTool]` becomes a callable tool when the object is passed to `$server->register(...)` (or, more commonly, registered in bulk — see `mcp.inc.php` below):

```php
class TaskTools
{
    public function __construct(private InstanceManager $manager) {}

    #[McpTool(description: 'Get a task by ID.', readOnlyHint: true)]
    public function phorge_task_get(string $task_id, ?string $instance = null): ToolResult
    {
        // ...
    }
}
```

## `mcp.inc.php`: loading and registering tool classes

Every Enchilada MCP server app follows the same convention: one class per file under the app's `tools/` directory, filename matching class name (e.g. `tools/TaskTools.php` defines `TaskTools`). `mcp.inc.php` is the canonical, shared implementation of that convention — don't re-write it per app.

**Vendoring**: copy this file to the app's own `includes/mcp.inc.php`, the same way `system/autoload.inc.php` is vendored from `Enchilada/Framework` — fetch from this canonical source, never copy peer-to-peer between app projects. `system/bootstrap.inc.php`'s component loader picks up any `includes/*.inc.php` automatically, so nothing else needs to reference it by path.

**What it provides:**

- A lazy `spl_autoload_register` for unnamespaced classes under `APPLICATION_ROOT . 'tools/'`.
- `enchilada_mcp_register_tools(McpServer $server, ?callable $onRegister, mixed ...$args): array` — scans `tools/`, resolves each file to a class via the autoloader above (`class_exists()`, not a second manual `require`), instantiates it with `...$args`, and registers it on `$server`. Returns the class names it registered, in scan order.

`scandir()`, not `glob()`: `glob()` never traverses the `phar://` stream wrapper (a long-standing PHP limitation — `file_exists()`/`is_dir()`/`scandir()` all work fine over it, `glob()` does not), so a phar build using `glob()` for discovery silently finds nothing. This bit `phorge-mcp` in practice: the built phar registered zero tools with no error at all.

**Usage**, from the app's composition root (after constructing `$server` and whatever the tool classes' constructor needs — `$manager` below is just an example, it's app-specific):

```php
$server = new McpServer('my-mcp-server', APPLICATION_VERSION);
$server->setLogger($logger);

enchilada_mcp_register_tools($server, function (string $className) use ($logger) {
    $logger->debug("Registered tools: {$className}");
}, $manager);
```

Call it explicitly at the composition root rather than expecting `mcp.inc.php` to call it itself: at include time (during `system/bootstrap.inc.php`'s component loader) `$server` and the tool classes' constructor arguments don't exist yet — they're built afterward, by the app. It's also a real hook, not just a technicality: an app may want to register a filtered subset of `tools/` (a read-only mode, a feature flag) or skip registration entirely (a test that only exercises transport/protocol behavior against a bare `McpServer`).

## Example: full server wiring

See `phorge-mcp` (`pacyworld/phorge-mcp`, `bin/phorge-mcp`) for a complete, working composition root — the reference implementation these docs describe, and the template for wiring up any new Enchilada MCP server app.
