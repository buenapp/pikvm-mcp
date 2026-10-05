<?php
/**
 * PiKVM MCP Server — HID Tools (keyboard + mouse)
 *
 * @package    PikvmMCP\Tools
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Pikvm\InstanceManager;
use Pikvm\KeyMap;
use Pikvm\PikvmException;

class HidTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'pikvm_send_key',
		description: 'Send a key press to the target. Give a friendly name (enter, esc, tab, f1..f12, up/down/left/right, capslock) or a raw kvmd code (KeyX, Digit3, ArrowUp, ControlLeft...).',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'key'      => ['type' => 'string', 'description' => 'Key to press'],
				'modifiers'=> ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Optional modifiers held during the keypress (ctrl, alt, shift, meta)'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['key'],
		]
	)]
	public function pikvm_send_key(string $key, array $modifiers = [], string $instance = ''): array
	{
		$target = $this->manager->instance($instance);
		$code = KeyMap::map($key);
		$mods = KeyMap::map($modifiers);
		$target->keyPress($code, is_array($mods) ? $mods : [$mods]);
		return ['status' => 'ok', 'action' => 'key', 'key' => $key, 'code' => $code];
	}

	#[McpTool(
		name: 'pikvm_send_shortcut',
		description: 'Send a modifier combination, e.g. ["ctrl","c"] or ["ctrl","alt","delete"]. Pressed in modifier order and released in reverse.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'keys'     => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Keys to combine; last entries are the action keys'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['keys'],
		]
	)]
	public function pikvm_send_shortcut(array $keys, string $instance = ''): array
	{
		if ($keys === []) {
			throw new PikvmException('no keys given', 0, 'InvalidArgument');
		}
		$target = $this->manager->instance($instance);
		$codes = KeyMap::map($keys);
		$mods = KeyMap::modifiersOf($codes);
		$actions = array_values(array_diff($codes, $mods));
		if ($actions === []) {
			throw new PikvmException('shortcut had only modifiers, no action key', 0, 'InvalidArgument');
		}
		foreach ($actions as $action) {
			$target->keyPress($action, $mods);
		}
		return ['status' => 'ok', 'action' => 'shortcut', 'keys' => $keys, 'codes' => $codes];
	}

	#[McpTool(
		name: 'pikvm_type_text',
		description: 'Type a string of text at the current focus (US layout).\\n sends newline via keymap.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'text'     => ['type' => 'string', 'description' => 'Text to type'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['text'],
		]
	)]
	public function pikvm_type_text(string $text, string $instance = ''): array
	{
		$this->manager->instance($instance)->typeText($text);
		return ['status' => 'ok', 'action' => 'type', 'length' => strlen($text)];
	}

	#[McpTool(
		name: 'pikvm_mouse_move',
		description: 'Move the absolute mouse cursor to pixel coordinates (x, y) without clicking.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'x'        => ['type' => 'integer'],
				'y'        => ['type' => 'integer'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['x', 'y'],
		]
	)]
	public function pikvm_mouse_move(int $x, int $y, string $instance = ''): array
	{
		$this->manager->instance($instance)->mouseMove($x, $y);
		return ['status' => 'ok', 'action' => 'move', 'x' => $x, 'y' => $y];
	}

	#[McpTool(
		name: 'pikvm_mouse_click',
		description: 'Click at pixel coordinates (x, y). button: left|right|middle; double for double-click.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'x'        => ['type' => 'integer'],
				'y'        => ['type' => 'integer'],
				'button'   => ['type' => 'string', 'enum' => ['left', 'right', 'middle']],
				'double'   => ['type' => 'boolean'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['x', 'y'],
		]
	)]
	public function pikvm_mouse_click(int $x, int $y, string $button = 'left', bool $double = false, string $instance = ''): array
	{
		$this->manager->instance($instance)->mouseClick($x, $y, $button, $double);
		return ['status' => 'ok', 'action' => 'click', 'x' => $x, 'y' => $y, 'button' => $button, 'double' => $double];
	}

	#[McpTool(
		name: 'pikvm_mouse_scroll',
		description: 'Scroll at position (x, y). amount>0 is up, <0 is down. Each click = one wheel step.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'x'        => ['type' => 'integer'],
				'y'        => ['type' => 'integer'],
				'amount'   => ['type' => 'integer'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['x', 'y', 'amount'],
		]
	)]
	public function pikvm_mouse_scroll(int $x, int $y, int $amount, string $instance = ''): array
	{
		$this->manager->instance($instance)->mouseScroll($x, $y, $amount);
		return ['status' => 'ok', 'action' => 'scroll', 'amount' => $amount];
	}

	#[McpTool(
		name: 'pikvm_hid_state',
		description: 'Read the target-side HID gadget state (keyboard online, mouse online, LED states). Useful for diagnosing input delivery.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
		]
	)]
	public function pikvm_hid_state(string $instance = ''): array
	{
		return $this->manager->instance($instance)->hidState();
	}
}
