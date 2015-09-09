<?php
App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * This feature class allows you to do fast structure and data copies from a seed
 * database. This is particularly helpful when you need big datasets to support your
 * testing.
 *
 * This also alleviates the strain of maintaining PHP based fixtures as you can use any
 * database migration tool to keep it up to date.
 *
 * Additionally it will inspect the database table hashes and detect any change to the underlying
 * data set and automatically re-create the table and data
 *
 */
class TableCopyTestFixture extends CakeTestFixture {

/**
 * The database config name from which the table will be copied
 *
 * @var string
 */
	public $sourceConfig = 'test_seed';

/**
 * List of table hashes
 *
 * @var array
 */
	public static $_tableHashes = [];

/**
 * Initializes this fixture class
 *
 * @param DboSource $db
 * @return boolean
 */
	public function create($db) {
		if (!empty($this->fields)) {
			return parent::create($db);
		}

		$source = ConnectionManager::getDataSource($this->sourceConfig);
		$sourceTable = $source->fullTableName($this->table);

		$query = sprintf('DROP TABLE IF EXISTS %s', $db->fullTableName($this->table));
		$db->execute($query, ['log' => false]);

		$query = sprintf('CREATE TABLE %s LIKE %s', $db->fullTableName($this->table), $sourceTable);
		$db->execute($query, ['log' => false]);

		$this->created[] = $db->configKeyName;
		return true;
	}

/**
 * Inserts records in the database
 *
 * This will only happen if the underlying table is modified in any way or
 * does not exist with a hash yet.
 *
 * @param DboSource $db
 * @return boolean
 */
	public function insert($db) {
		if ($this->_tableUnmodified($db)) {
			return true;
		}

		if (!empty($this->records)) {
			if (empty($this->fields)) {
				$this->fields = $db->describe($this->table);
			}

			$result = parent::insert($db);
			static::$_tableHashes[$this->table] = $this->_hash($db);
			return $result;
		}

		$source = ConnectionManager::getDataSource($this->sourceConfig);
		$sourceTable = $source->fullTableName($this->table);

		$query = sprintf('TRUNCATE TABLE %s', $db->fullTableName($this->table));
		$db->execute($query, ['log' => false]);

		$query = sprintf('INSERT INTO %s SELECT * FROM %s', $db->fullTableName($this->table), $sourceTable);
		$db->execute($query, ['log' => false]);

		static::$_tableHashes[$this->table] = $this->_hash($db);

		return true;
	}

/**
 * Deletes all table information.
 *
 * This will only happen if the underlying table is modified in any way
 *
 * @param DboSource $db
 * @return void
 */
	public function truncate($db) {
		if ($this->_tableUnmodified($db)) {
			return true;
		}

		return parent::truncate($db);
	}

/**
 * Drops the table from the test datasource
 *
 * @param DboSource $db
 * @return void
 */
	public function drop($db) {
		unset(static::$_tableHashes[$this->table]);

		return parent::drop($db);
	}

/**
 * Test if a table is modified or not
 *
 * If there is no known hash, treat it as being modified
 *
 * In all other cases where the initial and current hash differs, assume
 * the table has changed
 *
 * @param DboSource $db
 * @return boolean
 */
	protected function _tableUnmodified($db) {
		if (empty(static::$_tableHashes[$this->table])) {
			return false;
		}

		if (static::$_tableHashes[$this->table] === $this->_hash($db)) {
			return true;
		}

		return false;
	}

/**
 * Get the table hash from MySQL for a specific table
 *
 * @param DboSource $db
 * @return string
 */
	protected function _hash($db) {
		$db_conn = $db->getConnection();
		$sth = $db_conn->prepare("CHECKSUM TABLE " . $this->table);
		$sth->execute();
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		$checksum = $result['Checksum'];

		return $checksum;
	}

}
