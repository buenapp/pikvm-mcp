<?php
/**
 * PiKVM MCP Server — Instance Manager
 *
 * Multi-instance registry: instances.json -> Instance cache -> default.
 * Mirrors the vcenter-mcp InstanceManager pattern.
 *
 *   {
 *     "default": "pikvm-2",
 *     "instances": {
 *       "pikvm-2": {
 *         "url": "https://pikvm-2.morante.com",
 *         "user": "admin",
 *         "password": "…",
 *         "verify_ssl": false
 *       }
 *     }
 *   }
 *
 * @package    PikvmMCP\Pikvm
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Pikvm;

class InstanceManager
{
	/** @var array<string,Instance> */
	private array $instances = [];
	private string $default = '';

	/**
	 * @param array<string,array> $instances
	 */
	public function __construct(array $instances, string $default = '')
	{
		foreach ($instances as $name => $config) {
			$this->instances[$name] = new Instance($name, $config);
		}
		$this->default = $default;
		if ($this->default === '' && $this->instances !== []) {
			$this->default = (string)array_key_first($this->instances);
		}
		$this->instance(''); // fail fast if config is broken
	}

	public static function fromFile(string $path): self
	{
		if (!is_file($path)) {
			throw new PikvmException("instances.json not found: {$path}", 0, 'ConfigError');
		}
		$decoded = json_decode((string)file_get_contents($path), true);
		if (!is_array($decoded) || empty($decoded['instances']) || !is_array($decoded['instances'])) {
			throw new PikvmException("instances.json is malformed: needs an 'instances' object", 0, 'ConfigError');
		}
		return new self($decoded['instances'], (string)($decoded['default'] ?? ''));
	}

	/** Resolve a param, falling back to the configured default. */
	public function instance(string $name = ''): Instance
	{
		$name = ($name === '') ? $this->default : $name;
		if (!isset($this->instances[$name])) {
			throw new PikvmException(
				"no such instance '{$name}'; configured: " . implode(', ', array_keys($this->instances)),
				0, 'NotFound'
			);
		}
		return $this->instances[$name];
	}

	/** @return array<string,Instance> */
	public function all(): array
	{
		return $this->instances;
	}

	public function defaultName(): string
	{
		return $this->default;
	}
}
