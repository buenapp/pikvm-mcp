<?php
/**
 * PiKVM MCP Server — ATX Power Tools
 *
 * @package    PikvmMCP\Tools
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Pikvm\InstanceManager;
use Pikvm\PikvmException;

class AtxTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * kvmd 4.175 ATX actions: on|off|reset_hard (long press).
	 * Exposed granularity lets the caller choose soft vs hard.
	 */
	private const ACTIONS = ['on', 'off', 'reset_hard'];

	#[McpTool(
		name: 'pikvm_power',
		description: 'Hardware ATX power control on the target: on, off (long press, like holding the button), or reset_hard (reset button). DESTRUCTIVE: off/reset_hard reboot the target at hardware level.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'action'   => ['type' => 'string', 'description' => 'on | off | reset_hard'],
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
			'required' => ['action'],
		]
	)]
	public function pikvm_power(string $action, string $instance = ''): array
	{
		if (!in_array($action, self::ACTIONS, true)) {
			throw new PikvmException(
				"unknown action '{$action}'; valid: " . implode(',', self::ACTIONS),
				0, 'InvalidArgument'
			);
		}
		$target = $this->manager->instance($instance);
		$target->powerAction($action);
		return ['status' => 'ok', 'action' => $action];
	}

	#[McpTool(
		name: 'pikvm_power_state',
		description: 'Read ATX LED state: whether the target is powered on.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
		]
	)]
	public function pikvm_power_state(string $instance = ''): array
	{
		return $this->manager->instance($instance)->powerState();
	}
}
