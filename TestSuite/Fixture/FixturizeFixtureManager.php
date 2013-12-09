<?php

App::uses('CakeFixtureManager', 'TestSuite/Fixture');

/**
 * Nodes modified FixtureManager
 *
 * Makes loading the fixtures so much easier
 * and since everything is protected in CakeFixtureManager
 * we need our own class to hack around it
 *
 */
class FixturizeFixtureManager extends CakeFixtureManager {

/**
 * Load a list of $fixtures into a $source
 *
 * @param string $source The name of your datasource (e.g. default)
 * @param array $fixtures An array of fixtures - same format as in CakeTest $fixtures
 * @return void
 */
	public function loadAllFixtures($source, $fixtures) {
		$this->_initDb($source);
		try {
			$this->_loadFixtures($fixtures);
		} catch (Exception $e ){
			CakeLog::error('-> ' . $e->getMessage(), array('fixturize'));
		}

		CakeLog::debug('Begin fixture import', array('fixturize'));

		$nested = $this->_db->useNestedTransactions;
		$this->_db->useNestedTransactions = false;
		$this->_db->begin();
		foreach ($fixtures as $f) {
			CakeLog::debug(sprintf('Working on %s', $f));
			if (empty($this->_loaded[$f])) {
				CakeLog::warning('-> Cannot find it in the loaded array', array('fixturize'));
				continue;
			}

			$fixture = $this->_loaded[$f];
			CakeLog::debug(sprintf('-> Found fixture: %s', get_class($fixture)), array('fixturize'));

			$this->_setupTable($fixture, $this->_db, true);
			CakeLog::debug('-> Created table "OK"', array('fixture'));

			if ($fixture->insert($this->_db)) {
				CakeLog::debug('-> Inserted fixture data "OK"', array('fixturize'));
			} else {
				CakeLog::error('-> Inserted fixture data "ERROR"', array('fixturize'));
			}
		}

		$this->_db->commit();
		$this->_useNestedTransactions = $nested;
		CakeLog::debug('Done!', array('fixturize'));
	}

/**
 * Overridden so we can change datasource easily
 *
 * @param string $source Name of the datasource
 * @return void
 */
	protected function _initDb($source = 'default') {
		if ($this->_initialized) {
			return;
		}
		$db = ConnectionManager::getDataSource($source);
		$db->cacheSources = false;
		$this->_db = $db;
		$this->_initialized = true;
	}

}
