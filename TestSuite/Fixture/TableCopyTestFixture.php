<?php

App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * This feature class allows you to do fast structure and data copies from a seed
 * database. This is particularly helpful when you need big datasets to support your
 * testing.
 *
 * This also alleviates the strain of maintaining php based fixtures as you can use any
 * database migration tool to keep it up to date.
 *
 * Additionally it will inspect the database log and conditionally drop tables in between
 * tests depending on the presence of INSERT, UPDATE or DELETE statements
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
 * Whether any data was inserted on this fixture or not
 *
 * @var boolean
 */
	public $hasData = false;

/**
 * Whether fixtures are being truncated
 *
 * @var bool
 */
	public static $truncating = false;

/**
 * Holds the list of queries done to the database since the last time
 * it was truncated
 *
 * @var string
 */
	public static $log = array();

/**
 * Initializes this fixture class
 *
 * @return boolean
 */
	public function create($db) {
		if (!empty($this->fields)) {
			return parent::create($db);
		}
		$source = ConnectionManager::getDataSource($this->sourceConfig);
		$sourceTable = $source->fullTableName($this->table);
		$query = sprintf('CREATE TABLE IF NOT EXISTS %s like %s', $db->fullTableName($this->table), $sourceTable);
		$db->execute($query, array('log' => false));
		$this->created[] = $db->configKeyName;
		return true;
	}

/**
 * Inserts records in the database
 *
 * @param DboSource $db
 * @return boolean
 */
	public function insert($db) {
		self::$truncating = false;
		if ($this->hasData) {
			return true;
		}

		$this->hasData = true;

		if (!empty($this->records)) {
			return parent::insert($db);
		}

		$source = ConnectionManager::getDataSource($this->sourceConfig);
		$sourceTable = $source->fullTableName($this->table);
		$query = sprintf('INSERT INTO %s SELECT * FROM %s', $db->fullTableName($this->table), $sourceTable);
		$db->execute($query, array('log' => false));
		return true;
	}

/**
 * Deletes all table information. This will be done conditionally
 * depending on the presence of CREATE, INSERT or UPDATE statements
 * in the database log
 *
 * @return void
 */
	public function trucate($db) {
		if (!self::$truncating) {
			self::$log = $db->getLog();
			self::$truncating = true;
		}
		foreach (self::$log as $i => $q) {
			if (!preg_match('/^UPDATE|^INSERT|^DELETE/i', $q['query'])) {
				unset(self::$log[$i]);
				continue;
			}
			if (strpos($this->table, $q['query'])) {
				unset(self::$log[$i]);
				$this->hasData = false;
				return parent::truncate($db);
			}
		}

		return true;
	}
}
