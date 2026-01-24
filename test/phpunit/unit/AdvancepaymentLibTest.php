<?php
/**
 * Standalone unit tests for advancepayment library functions
 * Tests the library functions without full Dolibarr dependency
 *
 * Run with: phpunit htdocs/custom/advancepayment/test/phpunit/unit/AdvancepaymentLibTest.php
 */

use PHPUnit\Framework\TestCase;

// Define required constants for standalone testing
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', '/tmp');
}

// Mock global variables and functions needed by the library
global $langs, $conf;

// Mock $langs object
$langs = new class {
	public function load($file) {}
	public function trans($key) { return $key; }
};

// Mock $conf object
$conf = new class {
	public $modules = array();
};

// Mock complete_head_from_modules function
if (!function_exists('complete_head_from_modules')) {
	function complete_head_from_modules($conf, $langs, $object, &$head, &$h, $type, $mode = '') {}
}

// Mock dol_buildpath function
if (!function_exists('dol_buildpath')) {
	function dol_buildpath($path, $type = 0) {
		if ($type == 1) {
			return '/dolibarr' . $path;
		}
		return $path;
	}
}

require_once dirname(__FILE__).'/../../../lib/advancepayment.lib.php';

class AdvancepaymentLibTest extends TestCase
{
	// ========================================
	// Tests for advancepaymentAdminPrepareHead()
	// ========================================

	public function testAdminPrepareHeadReturnsArray()
	{
		$result = advancepaymentAdminPrepareHead();

		$this->assertIsArray($result);
	}

	public function testAdminPrepareHeadContainsSettingsTab()
	{
		$result = advancepaymentAdminPrepareHead();

		$settingsFound = false;
		foreach ($result as $tab) {
			if (isset($tab[2]) && $tab[2] === 'settings') {
				$settingsFound = true;
				break;
			}
		}

		$this->assertTrue($settingsFound, 'Settings tab should be present');
	}

	public function testAdminPrepareHeadContainsAboutTab()
	{
		$result = advancepaymentAdminPrepareHead();

		$aboutFound = false;
		foreach ($result as $tab) {
			if (isset($tab[2]) && $tab[2] === 'about') {
				$aboutFound = true;
				break;
			}
		}

		$this->assertTrue($aboutFound, 'About tab should be present');
	}

	public function testAdminPrepareHeadSettingsTabHasCorrectUrl()
	{
		$result = advancepaymentAdminPrepareHead();

		foreach ($result as $tab) {
			if (isset($tab[2]) && $tab[2] === 'settings') {
				$this->assertStringContainsString('/advancepayment/admin/setup.php', $tab[0]);
				break;
			}
		}
	}

	public function testAdminPrepareHeadAboutTabHasCorrectUrl()
	{
		$result = advancepaymentAdminPrepareHead();

		foreach ($result as $tab) {
			if (isset($tab[2]) && $tab[2] === 'about') {
				$this->assertStringContainsString('/advancepayment/admin/about.php', $tab[0]);
				break;
			}
		}
	}

	public function testAdminPrepareHeadSettingsTabHasLabel()
	{
		$result = advancepaymentAdminPrepareHead();

		foreach ($result as $tab) {
			if (isset($tab[2]) && $tab[2] === 'settings') {
				$this->assertEquals('Settings', $tab[1]);
				break;
			}
		}
	}

	public function testAdminPrepareHeadAboutTabHasLabel()
	{
		$result = advancepaymentAdminPrepareHead();

		foreach ($result as $tab) {
			if (isset($tab[2]) && $tab[2] === 'about') {
				$this->assertEquals('About', $tab[1]);
				break;
			}
		}
	}

	public function testAdminPrepareHeadHasMinimumTwoTabs()
	{
		$result = advancepaymentAdminPrepareHead();

		$this->assertGreaterThanOrEqual(2, count($result));
	}

	public function testAdminPrepareHeadTabsHaveCorrectStructure()
	{
		$result = advancepaymentAdminPrepareHead();

		foreach ($result as $index => $tab) {
			$this->assertIsArray($tab, "Tab at index $index should be an array");
			$this->assertArrayHasKey(0, $tab, "Tab at index $index should have URL at position 0");
			$this->assertArrayHasKey(1, $tab, "Tab at index $index should have label at position 1");
			$this->assertArrayHasKey(2, $tab, "Tab at index $index should have code at position 2");
		}
	}

	public function testAdminPrepareHeadSettingsTabComesFirst()
	{
		$result = advancepaymentAdminPrepareHead();

		$this->assertEquals('settings', $result[0][2]);
	}

	public function testAdminPrepareHeadAboutTabComesSecond()
	{
		$result = advancepaymentAdminPrepareHead();

		$this->assertEquals('about', $result[1][2]);
	}
}
