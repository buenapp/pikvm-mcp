<?php
/**
 * PiKVM MCP Server — Info / Adapter Tools
 *
 * @package    PikvmMCP\Tools
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use Pikvm\InstanceManager;

class InfoTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'pikvm_list_instances',
		description: 'List configured PiKVM instances and which one is the default.',
		readOnlyHint: true,
		inputSchema: ['type' => 'object', 'properties' => new stdClass()]
	)]
	public function pikvm_list_instances(): array
	{
		$out = ['default' => $this->manager->defaultName(), 'instances' => []];
		foreach ($this->manager->all() as $name => $inst) {
			$out['instances'][$name] = ['is_default' => $name === $this->manager->defaultName()];
		}
		return $out;
	}

	#[McpTool(
		name: 'pikvm_adapter_info',
		description: 'Return adapter/server metadata so the AI knows what this MCP can do (pre-login HID, ATX, screenshots).',
		readOnlyHint: true,
		inputSchema: ['type' => 'object', 'properties' => new stdClass()]
	)]
	public function pikvm_adapter_info(): array
	{
		return [
			'adapter' => 'pikvm',
			'server'  => APPLICATION_SLUG . ' ' . APPLICATION_VERSION,
			'kvmd_api' => 'form-encoded, validated via {ok} envelope',
			'capabilities' => [
				'pre_login_control' => true,
				'bios_control'      => true,
				'crash_recovery'    => true,
				'atx_power'         => true,
				'msd'               => false,
			],
		];
	}
}
