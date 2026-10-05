<?php
/**
 * PiKVM MCP Server — Screen Tools
 *
 * @package    PikvmMCP\Tools
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

use EnchiladaMCP\McpTool;
use EnchiladaMCP\ToolResult;
use Pikvm\InstanceManager;
use Pikvm\PikvmException;

class ScreenTools
{
	private InstanceManager $manager;

	public function __construct(InstanceManager $manager)
	{
		$this->manager = $manager;
	}

	#[McpTool(
		name: 'pikvm_screenshot',
		description: 'Take a screenshot of the PiKVM target. Returns a JPEG image that the AI can view. Use before acting on the target.',
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
		]
	)]
	public function pikvm_screenshot(string $instance = ''): ToolResult
	{
		$jpeg = $this->manager->instance($instance)->snapshot();
		if ($jpeg === '' || substr($jpeg, 0, 2) !== "\xFF\xD8") {
			throw new PikvmException('snapshot did not return JPEG data (' . strlen($jpeg) . ' bytes)', 0, 'StreamError');
		}
		return ToolResult::image(base64_encode($jpeg), 'image/jpeg');
	}

	#[McpTool(
		name: 'pikvm_screen_size',
		description: 'Return target screen resolution and streamer online state. Use to interpret pixel coordinates for clicks.',
		readOnlyHint: true,
		inputSchema: [
			'type' => 'object',
			'properties' => [
				'instance' => ['type' => 'string', 'description' => 'Instance name; default used if omitted'],
			],
		]
	)]
	public function pikvm_screen_size(string $instance = ''): array
	{
		return $this->manager->instance($instance)->screenSize();
	}
}
