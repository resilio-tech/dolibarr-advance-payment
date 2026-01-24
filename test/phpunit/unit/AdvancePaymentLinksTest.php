<?php
/**
 * Standalone unit tests for AdvancePaymentLinks
 * Uses mock database to test without Dolibarr dependency
 *
 * Run with: phpunit htdocs/custom/advancepayment/test/phpunit/unit/AdvancePaymentLinksTest.php
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
}
class CommonObject {
	public $db;
	public $id;
	public $ismultientitymanaged;
	public $isextrafieldmanaged;
	public $errors = array();
}
class User { public $id = 1; }
function getDolGlobalInt($key) { return 0; }
function isModEnabled($module) { return false; }
function dol_syslog($message, $level = LOG_DEBUG) {}
function dol_print_error($db, $message = \'\') {}
');
}

// Load the mock CommonObject which defines DoliDB base class
require_once $mocksDir.'/commonobject.class.php';

/**
 * Mock DoliDB class for testing - extends base DoliDB for type compatibility
 */
class MockDoliDB extends DoliDB
{
	/**
	 * @var array Results to return from queries
	 */
	private $queryResults = array();

	/**
	 * @var int Current result index
	 */
	private $resultIndex = 0;

	/**
	 * @var array Executed queries
	 */
	public $executedQueries = array();

	/**
	 * @var array Current result set rows
	 */
	private $currentRows = array();

	/**
	 * @var int Current row index
	 */
	private $rowIndex = 0;

	/**
	 * Set expected query results
	 *
	 * @param array $rows Rows to return
	 * @return void
	 */
	public function setQueryResult($rows)
	{
		$this->currentRows = $rows;
		$this->rowIndex = 0;
	}

	/**
	 * Execute query
	 *
	 * @param string $sql SQL query
	 * @return bool|resource
	 */
	public function query($sql)
	{
		$this->executedQueries[] = $sql;
		$this->rowIndex = 0;
		return true;
	}

	/**
	 * Fetch object from result
	 *
	 * @param resource $result Result resource
	 * @return object|null
	 */
	public function fetch_object($result = null)
	{
		if ($this->rowIndex < count($this->currentRows)) {
			$row = $this->currentRows[$this->rowIndex];
			$this->rowIndex++;
			return (object) $row;
		}
		return null;
	}

	/**
	 * Free result
	 *
	 * @param resource $result Result resource
	 * @return void
	 */
	public function free($result = null)
	{
		// No-op for mock
	}

	/**
	 * Get last executed query
	 *
	 * @return string|null
	 */
	public function getLastQuery()
	{
		return end($this->executedQueries) ?: null;
	}

	/**
	 * Clear executed queries
	 *
	 * @return void
	 */
	public function clearQueries()
	{
		$this->executedQueries = array();
	}
}

// Include the class under test after mock is defined
require_once dirname(__FILE__).'/../../../class/advancepaymentlink.class.php';

class AdvancePaymentLinksTest extends TestCase
{
	/**
	 * @var MockDoliDB
	 */
	private $mockDb;

	/**
	 * @var AdvancePaymentLinks
	 */
	private $links;

	protected function setUp(): void
	{
		$this->mockDb = new MockDoliDB();
		$this->links = new AdvancePaymentLinks($this->mockDb);
	}

	// ========================================
	// Tests for getElementLinks()
	// ========================================

	public function testGetElementLinksReturnsEmptyArrayWhenNoResults()
	{
		$this->mockDb->setQueryResult(array());

		$result = $this->links->getElementLinks(123);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testGetElementLinksReturnsSingleLink()
	{
		$this->mockDb->setQueryResult(array(
			array('type_link' => 'commande', 'element_rowid' => 456)
		));

		$result = $this->links->getElementLinks(123);

		$this->assertCount(1, $result);
		$this->assertEquals('commande', $result[0]['type']);
		$this->assertEquals(456, $result[0]['id']);
	}

	public function testGetElementLinksReturnsMultipleLinks()
	{
		$this->mockDb->setQueryResult(array(
			array('type_link' => 'commande', 'element_rowid' => 456),
			array('type_link' => 'propal', 'element_rowid' => 789),
			array('type_link' => 'project', 'element_rowid' => 101)
		));

		$result = $this->links->getElementLinks(123);

		$this->assertCount(3, $result);
		$this->assertEquals('commande', $result[0]['type']);
		$this->assertEquals(456, $result[0]['id']);
		$this->assertEquals('propal', $result[1]['type']);
		$this->assertEquals(789, $result[1]['id']);
		$this->assertEquals('project', $result[2]['type']);
		$this->assertEquals(101, $result[2]['id']);
	}

	public function testGetElementLinksUsesCorrectSql()
	{
		$this->mockDb->setQueryResult(array());

		$this->links->getElementLinks(999);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString('SELECT type_link, element_rowid', $lastQuery);
		$this->assertStringContainsString('advancepayment_advancepaymentlink', $lastQuery);
		$this->assertStringContainsString('payment_rowid = 999', $lastQuery);
	}

	// ========================================
	// Tests for getPaymentLinks()
	// ========================================

	public function testGetPaymentLinksReturnsEmptyArrayWhenNoResults()
	{
		$this->mockDb->setQueryResult(array());

		$result = $this->links->getPaymentLinks('commande', 123);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testGetPaymentLinksReturnsSinglePayment()
	{
		$this->mockDb->setQueryResult(array(
			array('payment_rowid' => 456)
		));

		$result = $this->links->getPaymentLinks('commande', 123);

		$this->assertCount(1, $result);
		$this->assertEquals(456, $result[0]);
	}

	public function testGetPaymentLinksReturnsMultiplePayments()
	{
		$this->mockDb->setQueryResult(array(
			array('payment_rowid' => 456),
			array('payment_rowid' => 789),
			array('payment_rowid' => 101)
		));

		$result = $this->links->getPaymentLinks('propal', 123);

		$this->assertCount(3, $result);
		$this->assertEquals(456, $result[0]);
		$this->assertEquals(789, $result[1]);
		$this->assertEquals(101, $result[2]);
	}

	public function testGetPaymentLinksUsesCorrectSqlForCommande()
	{
		$this->mockDb->setQueryResult(array());

		$this->links->getPaymentLinks('commande', 123);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString('SELECT payment_rowid', $lastQuery);
		$this->assertStringContainsString('advancepayment_advancepaymentlink', $lastQuery);
		$this->assertStringContainsString("type_link = 'commande'", $lastQuery);
		$this->assertStringContainsString('element_rowid = 123', $lastQuery);
	}

	public function testGetPaymentLinksUsesCorrectSqlForPropal()
	{
		$this->mockDb->setQueryResult(array());

		$this->links->getPaymentLinks('propal', 456);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString("type_link = 'propal'", $lastQuery);
		$this->assertStringContainsString('element_rowid = 456', $lastQuery);
	}

	public function testGetPaymentLinksForSocUsesJoinQuery()
	{
		$this->mockDb->setQueryResult(array());

		$this->links->getPaymentLinks('soc', 789);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString('LEFT JOIN', $lastQuery);
		$this->assertStringContainsString('commande', $lastQuery);
		$this->assertStringContainsString('propal', $lastQuery);
		$this->assertStringContainsString('societe', $lastQuery);
		$this->assertStringContainsString('s.rowid = 789', $lastQuery);
		$this->assertStringContainsString('used = 0', $lastQuery);
	}

	// ========================================
	// Tests for removePaymentLinks()
	// ========================================

	public function testRemovePaymentLinksExecutesDeleteQuery()
	{
		$this->links->removePaymentLinks(123);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString('DELETE FROM', $lastQuery);
		$this->assertStringContainsString('advancepayment_advancepaymentlink', $lastQuery);
		$this->assertStringContainsString('payment_rowid = 123', $lastQuery);
	}

	// ========================================
	// Tests for usePaymentLinkFrom()
	// ========================================

	public function testUsePaymentLinkFromExecutesUpdateQuery()
	{
		$this->links->usePaymentLinkFrom('commande', 123, 456);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString('UPDATE', $lastQuery);
		$this->assertStringContainsString('advancepayment_advancepaymentlink', $lastQuery);
		$this->assertStringContainsString('used = 1', $lastQuery);
		$this->assertStringContainsString("type_link = 'commande'", $lastQuery);
		$this->assertStringContainsString('element_rowid = 123', $lastQuery);
		$this->assertStringContainsString('payment_rowid = 456', $lastQuery);
		$this->assertStringContainsString('used = 0', $lastQuery);
	}

	public function testUsePaymentLinkFromPropalExecutesUpdateQuery()
	{
		$this->links->usePaymentLinkFrom('propal', 789, 101);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString("type_link = 'propal'", $lastQuery);
		$this->assertStringContainsString('element_rowid = 789', $lastQuery);
		$this->assertStringContainsString('payment_rowid = 101', $lastQuery);
	}

	public function testUsePaymentLinkFromSocUsesJoinQuery()
	{
		$this->links->usePaymentLinkFrom('soc', 789, 456);

		$lastQuery = $this->mockDb->getLastQuery();
		$this->assertStringContainsString('UPDATE', $lastQuery);
		$this->assertStringContainsString('LEFT JOIN', $lastQuery);
		$this->assertStringContainsString('commande', $lastQuery);
		$this->assertStringContainsString('propal', $lastQuery);
		$this->assertStringContainsString('societe', $lastQuery);
		$this->assertStringContainsString('s.rowid = 789', $lastQuery);
		$this->assertStringContainsString('payment_rowid = 456', $lastQuery);
	}

	// ========================================
	// Tests for class instantiation
	// ========================================

	public function testClassCanBeInstantiated()
	{
		$links = new AdvancePaymentLinks($this->mockDb);
		$this->assertInstanceOf(AdvancePaymentLinks::class, $links);
	}
}
