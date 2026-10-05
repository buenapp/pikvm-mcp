<?php
/**
 * PiKVM MCP Server — Domain Exception
 *
 * @package    PikvmMCP\Pikvm
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Pikvm;

class PikvmException extends \Exception
{
	private string $kind;

	public function __construct(string $message, int $code = 0, string $kind = 'PikvmError')
	{
		parent::__construct($message, $code);
		$this->kind = $kind;
	}

	public function getKind(): string
	{
		return $this->kind;
	}
}
