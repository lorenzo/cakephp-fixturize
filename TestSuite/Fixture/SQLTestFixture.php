<?php

App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * Extends the base fixture class in order to load a SQL file for creating the table schema,
 * inserting records or both at the same time.
 *
 * As this is just an extension of the original Fixture class, you are allowed to define either
 * $fields or $records. This will allow you to either week the schema or the records in php and define
 * any special code you need in a separate SQL file.
 *
 * If you choose to have both schema and records inside the SQL file, make sure your table declaration contains
 * a "IF NOT EXISTS" to avoid errors when executing the statements multiple times.
 *
 * By default SQL files are loaded from [APP|PLUGIN]/Test/Fixture/SQL/
 *
 */
class SQLTestFixture extends CakeTestFixture {

/**
 * The plugin where the .sql file is located
 *
 * @var string
 */
	public $plugin;

/**
 * The sql file basename, you can override this property
 * to load an specific file name. The default file name
 * is built by Inflecting the $name property of this class to
 * a table using Inflector::tableize()
 *
 * @var string
 */
	public $file;

/**
 * Initializes this fixture class
 *
 * @return boolean
 */
	public function init() {
		if (empty($this->file)) {
			$this->file = Inflector::tableize($this->name) . '.sql';
		}
		return parent::init();
	}

/**
 * Initializes this fixture class
 *
 * @return boolean
 */
	public function create($db) {
		if (!empty($this->fields)) {
			return parent::create($db);
		}
		return (bool)$db->execute(file_get_contents($this->_getFilePath()));
	}

/**
 * Inserts records in the database
 *
 * @param DboSource $db
 * @return boolean
 */
	public function insert($db) {
		if (isset($this->_insert)) {
			return true;
		}
		if (!empty($this->records)) {
			return parent::insert($db);
		}
		return (bool)$db->execute(file_get_contents($this->_getFilePath()));
	}

/**
 * Returns the full path where the SQL file is located.
 *
 * @return string
 */
	protected function _getFilePath() {
		$path = TESTS;
		if (!empty($this->plugin)) {
			$path = CakePlugin::path($this->plugin) . 'Test' . DS;
		}
		$path .= 'Fixture' . DS . 'SQL' . DS . $this->file;

		if (!is_file($path)) {
			throw new CakeException('Could not find file: ' . $path);
		}
		return $path;
	}

}
