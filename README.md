# pikvm-mcp

PiKVM hardware console MCP server written in PHP on the Enchilada Framework.

Replaces `ki-sum/mcp-kvm` (Python), which sent JSON bodies to kvmd 4.175 —
an API version that only accepts form-encoded or query-string parameters —
resulting in silently dropped keyboard/mouse/ATX events with a fake
`{"status":"ok"}` response.

## Tools (13)

| Tool | Description |
|---|---|
| `pikvm_screenshot` | JPEG image of the target screen |
| `pikvm_screen_size` | Capture resolution + streamer online state |
| `pikvm_send_key` | Key press; friendly names or kvmd codes; optional modifiers |
| `pikvm_send_shortcut` | Modifier combos (ctrl+alt+del, ...) |
| `pikvm_type_text` | Type text (US keymap) |
| `pikvm_mouse_move` / `pikvm_mouse_click` / `pikvm_mouse_scroll` | Mouse at pixel coords |
| `pikvm_hid_state` | HID gadget online/LED state |
| `pikvm_power` | ATX power: on, off, reset_hard (kvmd 4.x actions) |
| `pikvm_power_state` | ATX LED / power state |
| `pikvm_list_instances` | Configured instances |
| `pikvm_adapter_info` | Capability advertisement |

All kvmd responses are envelope-validated (`ok: true` required) — unlike the
Python server, nothing fails silently.

## Configuration

`instances.json`:

```json
{
  "default": "pikvm-2",
  "instances": {
    "pikvm-2": {
      "url": "https://pikvm-2.example.com",
      "user": "admin",
      "password": "…",
      "verify_ssl": false
    }
  }
}
```

Pass with `PIKVM_MCP_CONFIG=/path/to/instances.json` or `--config=PATH`.

## MCP client registration

```json
"pikvm": {
  "command": "php",
  "args": ["/path/to/pikvm-mcp/bin/pikvm-mcp", "--config=/home/you/.config/pikvm-mcp/instances.json"]
}
```

## Key naming

Tools take friendly names (`enter`, `ctrl`, `shift`, `f1`, `capslock`,
`up`, `home`) or kvmd/Web codes (`KeyX`, `Digit3`, `ArrowUp`, `ControlLeft`).
The mapping table is in `classes/Pikvm/KeyMap.class.php`.

## License

BSD-2-Clause — Daniel Morante Company, Inc.
