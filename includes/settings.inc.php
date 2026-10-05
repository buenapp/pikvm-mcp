<?php
/**
 * PiKVM MCP Server — Settings Include
 *
 * Optional INI settings; the tool surface relies on instances.json via
 * PIKVM_MCP_CONFIG, but a settings.ini can still seed shared defaults.
 *
 * @package    PikvmMCP
 * @author     Daniel Morante (assisted by Devin)
 * @copyright  2026 The Daniel Morante Company, Inc.
 * @license    BSD-2-Clause
 */

$settingsFile = APPLICATION_CONFDIR . 'settings.ini';
$SETTINGS = is_file($settingsFile) ? parse_ini_file($settingsFile, true, INI_SCANNER_TYPED) : [];
