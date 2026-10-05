<?php
/**
 * PiKVM MCP Server — Application Configuration
 *
 * @package    PikvmMCP
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

define('APPLICATION_NAME', 'PiKVM MCP Server');
define('APPLICATION_SLUG', 'pikvm-mcp');
define('APPLICATION_WEBSITE', 'https://pacyworld.dev/buenapp/pikvm-mcp');
define('APPLICATION_VERSION', 'v0.1.0');
define('APPLICATION_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APPLICATION_CONFDIR', (getenv('PIKVM_MCP_CONF_DIR') ?: APPLICATION_ROOT . 'config' . DIRECTORY_SEPARATOR));
define('APPLICATION_DEBUG', getenv('ENCHILADA_DEBUG_ENABLE'));
define('APPLICATION_TEMPDIR', APPLICATION_ROOT . 'temp' . DIRECTORY_SEPARATOR);
define('APPLICATION_TIMEZONE', 'UTC');
define('APPLICATION_USERAGENT', sprintf('%s/%s (%s; %s) PHP %s', APPLICATION_SLUG, APPLICATION_VERSION, php_uname('s'), php_uname('r'), phpversion()));
