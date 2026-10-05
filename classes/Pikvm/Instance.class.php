<?php
/**
 * PiKVM MCP Server — Single Target Instance Wrapper
 *
 * @package    PikvmMCP\Pikvm
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Pikvm;

class Instance
{
	private string $name;
	private ApiClient $api;

	private static array $validAtxActions = ['on', 'off', 'reset_hard'];

	public function __construct(string $name, array $config)
	{
		$this->name = $name;
		foreach (['url', 'user', 'password'] as $required) {
			if (empty($config[$required])) {
				throw new PikvmException("Instance '{$name}': missing required config key '{$required}'", 0, 'ConfigError');
			}
		}
		$this->api = new ApiClient(
			$config['url'],
			$config['user'],
			$config['password'],
			(bool)($config['verify_ssl'] ?? false),
			(int)($config['timeout'] ?? 30)
		);
	}

	public function name(): string
	{
		return $this->name;
	}

	public function api(): ApiClient
	{
		return $this->api;
	}

	// ── Screens ────────────────────────────────────────────────────────

	/** Returns raw JPEG bytes. */
	public function snapshot(): string
	{
		// /api/streamer/snapshot ignores stale data when asked client-side;
		// append a cache-buster so a frozen framebuffer is obvious.
		return $this->api->getRawBody('/api/streamer/snapshot');
	}

	public function screenSize(): array
	{
		// ustreamer reports the negotiated capture resolution.
		$result = $this->api->get('/api/streamer');
		$src = ($result['streamer'] ?? $result)['source'] ?? [];
		$res = $src['resolution'] ?? [];
		return [
			'width'  => (int)($res['width'] ?? 0),
			'height' => (int)($res['height'] ?? 0),
			'online' => (bool)($src['online'] ?? false),
		];
	}

	// ── HID ───────────────────────────────────────────────────────────

	/** Single key with optional modifiers. Key names are kvmd codes (Enter, KeyA, F1, ControlLeft, ...). */
	public function keyPress(string $key, array $modifiers = []): void
	{
		foreach ($modifiers as $mod) {
			$this->api->post('/api/hid/events/send_key', ['key' => $mod, 'state' => 'true']);
		}
		try {
			$this->api->post('/api/hid/events/send_key', ['key' => $key, 'state' => 'true']);
			usleep(30_000);
			$this->api->post('/api/hid/events/send_key', ['key' => $key, 'state' => 'false']);
		} finally {
			foreach (array_reverse($modifiers) as $mod) {
				$this->api->post('/api/hid/events/send_key', ['key' => $mod, 'state' => 'false']);
			}
		}
	}

	public function typeText(string $text): void
	{
		// kvmd /api/hid/print takes raw text; keymap selects the layout map.
		$this->api->postText('/api/hid/print', $text, ['keymap' => 'en-us']);
	}

	// ── Mouse ─────────────────────────────────────────────────────────

	public function mouseMove(int $x, int $y, bool $relative = false): void
	{
		$endpoint = $relative ? '/api/hid/events/send_mouse_relative' : '/api/hid/events/send_mouse_move';
		$this->api->post($endpoint, ['to.x' => (string)$x, 'to.y' => (string)$y, 'mouse_slow' => 'false']);
	}

	public function mouseClick(int $x, int $y, string $button = 'left', bool $double = false): void
	{
		$this->mouseMove($x, $y);
		$count = $double ? 2 : 1;
		for ($i = 0; $i < $count; $i++) {
			$this->api->post('/api/hid/events/send_mouse_button', ['button' => $button, 'state' => 'true']);
			$this->api->post('/api/hid/events/send_mouse_button', ['button' => $button, 'state' => 'false']);
			usleep(40_000);
		}
	}

	public function mouseScroll(int $x, int $y, int $delta): void
	{
		$this->mouseMove($x, $y);
		// kvmd takes one wheel event per call; sign gives direction
		$n = abs($delta);
		$dir = $delta > 0 ? 1 : -1;
		for ($i = 0; $i < $n; $i++) {
			$this->api->post('/api/hid/events/send_mouse_wheel', ['delta.x' => '0', 'delta.y' => (string)$dir]);
		}
	}

	// ── ATX ───────────────────────────────────────────────────────────

	public function powerAction(string $action): void
	{
		if (!in_array($action, self::$validAtxActions, true)) {
			throw new PikvmException(
				"unknown ATX action '{$action}' (valid: " . implode(', ', self::$validAtxActions) . ")",
				0, 'InvalidArgument'
			);
		}
		$this->api->post('/api/atx/power', ['action' => $action]);
	}

	public function powerState(): array
	{
		$result = $this->api->get('/api/atx');
		return [
			'is_on'     => (bool)($result['leds']['power'] ?? false),
			'led_power' => (bool)($result['leds']['power'] ?? false),
			'led_hdd'   => (bool)($result['leds']['hdd'] ?? false),
			'builtin'   => $result['builtin'] ?? false,
		];
	}

	// ── Meta / state ──────────────────────────────────────────────────

	public function hidState(): array
	{
		$result = $this->api->get('/api/hid');
		return [
			'enabled'   => (bool)($result['enabled'] ?? false),
			'keyboard'  => $result['keyboard'] ?? [],
			'mouse'     => $result['mouse'] ?? [],
			'connected' => $result['connected'],
		];
	}
}
