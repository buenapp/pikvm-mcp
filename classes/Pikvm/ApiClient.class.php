<?php
/**
 * PiKVM MCP Server — kvmd HTTP API Client
 *
 * Speaks to PiKVM's kvmd REST API. Written against kvmd 4.175:
 *
 *   - ALL hid/atx/control endpoints take FORM-ENCODED data, never
 *     JSON bodies. (ki-sum/mcp-kvm's bug: it posts JSON, which
 *     old kvmd rejects with a ValidatorError it then swallows.)
 *   - Every control-plane response is `{ "ok": bool, "result": ... }`;
 *     ok=false raises PikvmException so failures never go silent.
 *
 * @package    PikvmMCP\Pikvm
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

namespace Pikvm;

use EnchiladaHTTP;

class ApiClient
{
	private EnchiladaHTTP $api;   // $base . '/api' prefix is NOT added; callers pass '/api/…'
	private EnchiladaHTTP $http;  // unused duplicate kept for binary endpoints

	public function __construct(string $baseUrl, string $user, string $password, bool $verifySsl = false, int $timeout = 30)
	{
		$base = rtrim($baseUrl, '/');
		$this->api = new EnchiladaHTTP($base);
		$this->api->setPlaintextAuth($user, $password);
		$this->api->setVerifySsl($verifySsl);
		$this->api->setTimeout($timeout);

		$this->http = new EnchiladaHTTP($base);
		$this->http->setPlaintextAuth($user, $password);
		$this->http->setVerifySsl($verifySsl);
		$this->http->setTimeout($timeout);
	}

	/**
	 * GET a JSON API endpoint; returns the decoded `result` member. Raises on ok=false.
	 */
	public function get(string $path)
	{
		$decoded = $this->api->call(ltrim($path, '/'), null, 'GET');
		return $this->unwrap($decoded, $path);
	}

	/**
	 * POST a JSON API endpoint with form-encoded params (the kvmd convention).
	 * Params are sent both as POST body AND query string — kvmd's validators
	 * historically read different sources on different endpoints.
	 */
	public function post(string $path, array $params = [])
	{
		$pathName = ltrim($path, '/');
		if ($params !== []) {
			$pathName .= '?' . http_build_query($params);
		}
		$decoded = $this->api->call($pathName, $params, 'POST', [], null, 'json');
		return $this->unwrap($decoded, $path);
	}

	/**
	 * POST raw text (used by type_text -> /api/hid/print).
	 */
	public function postText(string $path, string $text, array $query = []): void
	{
		$pathName = ltrim($path, '/');
		if ($query !== []) {
			$pathName .= '?' . http_build_query($query);
		}
		$decoded = $this->api->call($pathName, $text, 'POST', ['Content-Type: text/plain'], null, 'raw');
		$this->unwrap(is_string($decoded) ? json_decode($decoded, true) : $decoded, $path);
	}

	/**
	 * GET raw bytes (the JPEG snapshot endpoint).
	 */
	public function getRawBody(string $path): string
	{
		$body = $this->http->call(ltrim($path, '/'), null, 'GET', [], 60, 'raw');
		if (!is_string($body) || $body === '') {
			throw new PikvmException("{$path}: empty body (HTTP " . $this->http->getHttpCode() . ')', 0, 'HttpError');
		}
		return $body;
	}

	private function unwrap($decoded, string $path)
	{
		if (!is_array($decoded)) {
			throw new PikvmException("{$path}: kvmd returned non-JSON or transport error", 0, 'ProtocolError');
		}
		if (empty($decoded['ok'])) {
			$msg = $decoded['result']['error_msg'] ?? ($decoded['error'] ?? 'unknown kvmd error');
			throw new PikvmException("{$path}: kvmd error: {$msg}", 0, 'KvmdError');
		}
		return $decoded['result'] ?? null;
	}
}
