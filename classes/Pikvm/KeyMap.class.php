<?php
/**
 * PiKVM MCP Server — Friendly key name -> kvmd key code map
 *
 * kvmd's /api/hid/events/send_key uses Web `code` names (KeyA, Digit1,
 * Enter, F1, ControlLeft, ...). This map accepts user-facing names and
 * converts them to those codes.
 *
 * @package    PikvmMCP\Pikvm
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Pikvm;

final class KeyMap
{
	private const MAP = [
		'enter'      => 'Enter',
		'return'     => 'Enter',
		'tab'        => 'Tab',
		'esc'        => 'Escape',
		'escape'     => 'Escape',
		'space'      => 'Space',
		'backspace'  => 'Backspace',
		'bs'         => 'Backspace',
		'delete'     => 'Delete',
		'del'        => 'Delete',
		'up'         => 'ArrowUp',
		'down'       => 'ArrowDown',
		'left'       => 'ArrowLeft',
		'right'      => 'ArrowRight',
		'home'       => 'Home',
		'end'        => 'End',
		'pageup'     => 'PageUp',
		'pgup'       => 'PageUp',
		'pagedown'   => 'PageDown',
		'pgdn'       => 'PageDown',
		'insert'     => 'Insert',
		'ins'        => 'Insert',
		'capslock'   => 'CapsLock',
		'caps'       => 'CapsLock',
		'numlock'    => 'NumLock',
		'scrolllock' => 'ScrollLock',
		'printscreen'=> 'PrintScreen',
		'prsc'       => 'PrintScreen',
		'pauses'     => 'Pause',
		'pause'      => 'Pause',
		'menu'       => 'ContextMenu',
		// function keys
		'f1' => 'F1', 'f2' => 'F2', 'f3' => 'F3', 'f4' => 'F4',
		'f5' => 'F5', 'f6' => 'F6', 'f7' => 'F7', 'f8' => 'F8',
		'f9' => 'F9', 'f10' => 'F10', 'f11' => 'F11', 'f12' => 'F12',
		// modifiers (canonical kvmd names)
		'ctrl'       => 'ControlLeft',
		'control'    => 'ControlLeft',
		'ctrlleft'   => 'ControlLeft',
		'lctrl'      => 'ControlLeft',
		'ctrlright'  => 'ControlRight',
		'rctrl'      => 'ControlRight',
		'shift'      => 'ShiftLeft',
		'shiftleft'  => 'ShiftLeft',
		'lshift'     => 'ShiftLeft',
		'shitfright' => 'ShiftRight',
		'shiftright' => 'ShiftRight',
		'rshift'     => 'ShiftRight',
		'alt'        => 'AltLeft',
		'altleft'    => 'AltLeft',
		'lalt'       => 'AltLeft',
		'altgr'      => 'AltRight',
		'altright'   => 'AltRight',
		'ralt'       => 'AltRight',
		'win'        => 'MetaLeft',
		'meta'       => 'MetaLeft',
		'metaleft'   => 'MetaLeft',
		'lmeta'      => 'MetaLeft',
		'metaright'  => 'MetaRight',
		'rmeta'      => 'MetaRight',
	];

	/**
	 * Map friendly name or array of names to kvmd key code(s).
	 * @param string|string[] $keys
	 * @return string|string[]
	 */
	public static function map($keys)
	{
		if (is_array($keys)) {
			return array_map([self::class, 'map'], $keys);
		}
		$lower = strtolower(trim($keys));
		if (isset(self::MAP[$lower])) {
			return self::MAP[$lower];
		}
		// single alphanumeric -> KeyX / DigitN
		if (strlen($keys) === 1) {
			if (ctype_alpha($keys)) {
				return 'Key' . strtoupper($keys);
			}
			if (ctype_digit($keys)) {
				return 'Digit' . $keys;
			}
		}
		// Already a code: leave it.
		return $keys;
	}

	/** @return string[] */
	public static function modifiersOf(array $keys): array
	{
		$mods = ['ControlLeft', 'ControlRight', 'ShiftLeft', 'ShiftRight', 'AltLeft', 'AltRight', 'MetaLeft', 'MetaRight'];
		return array_values(array_intersect(array_map(function ($k) { return is_string($k) ? $k : ''; }, $keys), $mods));
	}
}
