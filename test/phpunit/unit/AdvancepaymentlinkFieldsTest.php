<?php
/**
 * Standalone unit tests for Advancepaymentlink fields definition
 * Tests the field configuration without Dolibarr dependency
 *
 * Run with: phpunit htdocs/custom/advancepayment/test/phpunit/unit/AdvancepaymentlinkFieldsTest.php
 */

use PHPUnit\Framework\TestCase;

// Define required constants before loading class
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/mocks');
}

// Create mocks directory and CommonObject mock if not exists
$mocksDir = dirname(__FILE__).'/mocks/core/class';
if (!is_dir($mocksDir)) {
	mkdir($mocksDir, 0755, true);
}
if (!file_exists($mocksDir.'/commonobject.class.php')) {
	file_put_contents($mocksDir.'/commonobject.class.php', '<?php
class DoliDB {
	public function query($sql) { return true; }
	public function fetch_object($result = null) { return null; }
	public function free($result = null) {}
	public function prefix() { return MAIN_DB_PREFIX; }
	public function num_rows($result) { return 0; }
	public function order($field, $order) { return " ORDER BY $field $order"; }
	public function plimit($limit, $offset) { return " LIMIT $limit OFFSET $offset"; }
}
class CommonObject {
	public $db;
	public $id;
	public $ismultientitymanaged;
	public $isextrafieldmanaged;
	public $errors = array();
	public $error = \'\';
	public function setVarsFromFetchObj($obj) {}
	public function fetch_optionals() {}
	public function createCommon($user, $notrigger = 0) { return 1; }
	public function fetchCommon($id, $ref = null, $morewhere = \'\', $noextrafields = 0) { return 1; }
	public function fetchLinesCommon($morewhere = \'\', $noextrafields = 0) { return 0; }
	public function updateCommon($user, $notrigger = 0) { return 1; }
	public function deleteCommon($user, $notrigger = 0, $forcechilddeletion = 0) { return 1; }
	public function deleteLineCommon($user, $idline, $notrigger = 0) { return 1; }
	public function getFieldList($alias = \'\') { return \'*\'; }
}
class User { public $id = 1; }
function getDolGlobalInt($key) { return 0; }
function isModEnabled($module) { return false; }
function dol_syslog($message, $level = LOG_DEBUG) {}
function dol_print_error($db, $message = \'\') {}
function forgeSQLFromUniversalSearchCriteria($filter, &$error) { return \'\'; }
function getEntity($element) { return \'1\'; }
');
}

// Load the mock CommonObject
require_once $mocksDir.'/commonobject.class.php';

// Include the class under test
require_once dirname(__FILE__).'/../../../class/advancepaymentlink.class.php';

class AdvancepaymentlinkFieldsTest extends TestCase
{
	/**
	 * @var DoliDB
	 */
	private $mockDb;

	/**
	 * @var Advancepaymentlink
	 */
	private $object;

	protected function setUp(): void
	{
		global $langs;
		$langs = new stdClass();
		$langs->trans = function($key) { return $key; };

		$this->mockDb = new DoliDB();
		$this->object = new Advancepaymentlink($this->mockDb);
	}

	// ========================================
	// Tests for class properties
	// ========================================

	public function testModulePropertyIsSet()
	{
		$this->assertEquals('advancepayment', $this->object->module);
	}

	public function testElementPropertyIsSet()
	{
		$this->assertEquals('advancepaymentlink', $this->object->element);
	}

	public function testTableElementPropertyIsSet()
	{
		$this->assertEquals('advancepayment_advancepaymentlink', $this->object->table_element);
	}

	public function testPictoPropertyIsSet()
	{
		$this->assertEquals('generic', $this->object->picto);
	}

	// ========================================
	// Tests for fields definition
	// ========================================

	public function testFieldsArrayExists()
	{
		$this->assertIsArray($this->object->fields);
	}

	public function testRequiredFieldsExist()
	{
		$requiredFields = array('rowid', 'type_link', 'payment_rowid', 'element_rowid', 'date_creation', 'fk_user_creat');

		foreach ($requiredFields as $field) {
			$this->assertArrayHasKey($field, $this->object->fields, "Field '$field' should exist");
		}
	}

	public function testRowidFieldConfiguration()
	{
		$field = $this->object->fields['rowid'];

		$this->assertEquals('integer', $field['type']);
		$this->assertEquals('TechnicalID', $field['label']);
		$this->assertEquals(1, $field['notnull']);
		$this->assertEquals('0', $field['visible']);
		$this->assertEquals('1', $field['index']);
	}

	public function testTypeLinkFieldConfiguration()
	{
		$field = $this->object->fields['type_link'];

		$this->assertEquals('varchar(255)', $field['type']);
		$this->assertEquals('TypeLink', $field['label']);
		$this->assertEquals(1, $field['notnull']);
	}

	public function testPaymentRowidFieldConfiguration()
	{
		$field = $this->object->fields['payment_rowid'];

		$this->assertStringStartsWith('integer:', $field['type']);
		$this->assertStringContainsString('PaymentVarious', $field['type']);
		$this->assertEquals('PaymentLink', $field['label']);
		$this->assertEquals(1, $field['notnull']);
	}

	public function testElementRowidFieldConfiguration()
	{
		$field = $this->object->fields['element_rowid'];

		$this->assertEquals('integer', $field['type']);
		$this->assertEquals('ElementLink', $field['label']);
		$this->assertEquals(1, $field['notnull']);
	}

	public function testDateCreationFieldConfiguration()
	{
		$field = $this->object->fields['date_creation'];

		$this->assertEquals('datetime', $field['type']);
		$this->assertEquals('DateCreation', $field['label']);
		$this->assertEquals(1, $field['notnull']);
	}

	public function testTmsFieldConfiguration()
	{
		$field = $this->object->fields['tms'];

		$this->assertEquals('timestamp', $field['type']);
		$this->assertEquals('DateModification', $field['label']);
		$this->assertEquals(0, $field['notnull']);
	}

	public function testUserCreatFieldConfiguration()
	{
		$field = $this->object->fields['fk_user_creat'];

		$this->assertStringStartsWith('integer:', $field['type']);
		$this->assertStringContainsString('User', $field['type']);
		$this->assertEquals('UserAuthor', $field['label']);
		$this->assertEquals('user', $field['picto']);
	}

	public function testUserModifFieldConfiguration()
	{
		$field = $this->object->fields['fk_user_modif'];

		$this->assertStringStartsWith('integer:', $field['type']);
		$this->assertStringContainsString('User', $field['type']);
		$this->assertEquals('UserModif', $field['label']);
		$this->assertEquals('user', $field['picto']);
	}

	// ========================================
	// Tests for field positions
	// ========================================

	public function testFieldPositionsAreOrdered()
	{
		$positions = array();
		foreach ($this->object->fields as $key => $field) {
			$positions[$key] = $field['position'];
		}

		$sortedPositions = $positions;
		asort($sortedPositions);

		$this->assertEquals(array_keys($sortedPositions), array_keys($sortedPositions));
	}

	public function testRowidIsFirstPosition()
	{
		$this->assertEquals(1, $this->object->fields['rowid']['position']);
	}

	public function testAuditFieldsAreAtEnd()
	{
		$auditFields = array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif');

		foreach ($auditFields as $field) {
			$this->assertGreaterThanOrEqual(500, $this->object->fields[$field]['position'],
				"Field '$field' should have position >= 500");
		}
	}

	// ========================================
	// Tests for class object properties
	// ========================================

	public function testObjectPropertiesExist()
	{
		$properties = array('rowid', 'type_link', 'payment_rowid', 'element_rowid',
			'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif');

		foreach ($properties as $prop) {
			$this->assertTrue(property_exists($this->object, $prop),
				"Property '$prop' should exist on Advancepaymentlink object");
		}
	}

	// ========================================
	// Tests for enabled/visible configuration
	// ========================================

	public function testAllFieldsHaveEnabledSet()
	{
		foreach ($this->object->fields as $key => $field) {
			$this->assertArrayHasKey('enabled', $field,
				"Field '$key' should have 'enabled' set");
		}
	}

	public function testAllFieldsHaveVisibleSet()
	{
		foreach ($this->object->fields as $key => $field) {
			$this->assertArrayHasKey('visible', $field,
				"Field '$key' should have 'visible' set");
		}
	}

	// ========================================
	// Tests for entity management
	// ========================================

	public function testIsMultientityManagedIsSet()
	{
		$this->assertEquals(0, $this->object->ismultientitymanaged);
	}

	public function testIsExtrafieldManagedIsSet()
	{
		$this->assertEquals(1, $this->object->isextrafieldmanaged);
	}
}
