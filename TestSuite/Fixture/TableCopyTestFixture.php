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
class TableCopyTestFixture extends CakeTestFixture
{
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
    public static $hasData = [];

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
    public function create($db)
    {
        if (!empty($this->fields)) {
            return parent::create($db);
        }

        static::$hasData[$this->table] = false;

        $ReflectionProp = new ReflectionProperty(get_class($db), '_queriesLogMax');
        $ReflectionProp->setAccessible(true);
        $ReflectionProp->setValue($db, PHP_INT_MAX);

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
    public function insert($db)
    {
        if (!empty(static::$hasData[$this->table])) {
            return true;
        }

        static::$hasData[$this->table] = true;

        if (!empty($this->records)) {
            if (empty($this->fields)) {
                $this->fields = $db->describe($this->table);
            }

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
    public function truncate($db)
    {
        $ReflectionProp = new ReflectionProperty(get_class($db), '_queriesLog');
        $ReflectionProp->setAccessible(true);
        $log = $ReflectionProp->getValue($db);
        $truncated = false;

        foreach ($log as $i => $q) {
            if (in_array($q['query'], ['COMMIT', 'BEGIN', 'ROLLBACK'])) {
                unset($log[$i]);
                continue;
            }

            if (false === stripos($q['query'], $db->fullTableName($this->table))) {
                continue;
            }

            unset($log[$i]);

            if (!preg_match('/^UPDATE|^INSERT|^DELETE|^TRUNCATE|^ALTER/i', $q['query'])) {
                continue;
            }

            static::$hasData[$this->table] = false;
            $truncated = parent::truncate($db);
        }

        $ReflectionProp->setValue($db, $log);

        return true;
    }

/**
 * Drops the table from the test datasource
 *
 * @return void
 */
    public function drop($db)
    {
        $this->Schema->build(array($this->table => $this->fields));

        try {
            $db->execute('DROP TABLE ' . $db->fullTableName($this->table), array('log' => false));
            $this->created = array_diff($this->created, array($db->configKeyName));
            static::$hasData[$this->table] = false;
        } catch (Exception $e) {
            CakeLog::write('error', 'Failed to drop table: ' . $db->fullTableName($this->table) . ' - ' . $e->getMessage());

            return false;
        }

        return true;
    }

}
