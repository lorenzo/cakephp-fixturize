<?php
App::uses('FixturizeFixtureManager', 'Fixturize.TestSuite/Fixture');
App::uses('CakeTestFixture', 'TestSuite/Fixture');
App::uses('ConnectionManager', 'Model');
App::uses('Folder', 'Utility');

/**
 * Fixture Loader shell
 *
 * Example:
 *  ./Console/cake Fixturize.fixture_loader app.events,app.tags,app.categories
 *  ./Console/cake Fixturize.fixture_loader app.events,app.tags,app.categories --datasource production
 *
 * Allows you to load test fixtures into any data source you may have
 * It's useful  when you need to see your test data in-app, or just
 * need to update your fixtures in a more friendly interface than PHP arrays
 *
 * @author Christian Winther (https://github.com/Jippi)
 */
class FixtureLoaderShell extends AppShell {

/**
 * The one and only shell action
 *
 * Find the fixtures from command line and optionally a datasource, and import them
 *
 * @return void
 */
	public function main() {
		if (empty($this->args[0])) {
			$this->args[0] = $this->findAllFixtureFiles();
		}

		$CakeFixtureManager = new FixturizeFixtureManager();
		$CakeFixtureManager->loadAllFixtures($this->params['datasource'], explode(',', $this->args[0]));
	}

/**
 * Loads all fixture files for app or plugin
 *
 * @return array
 */
	public function findAllFixtureFiles() {
		$basePath = APP;
		if (is_string($this->params['plugin'])) {
			if (CakePlugin::loaded()) {
				$basePath = CakePlugin::path($this->params['plugin']);
			} else {
				$this->err(__('Plugin %s is not loaded or does not exist!', $this->params['plugin']));
			}
		}

		$Folder = new Folder($basePath . 'Test' . DS . 'Fixture');
		$folderContent = $Folder->read();
		$fixtures = '';

		if (!empty($folderContent[1])) {
			foreach ($folderContent[1] as $file) {
				if (substr($file, -11) === 'Fixture.php') {
					$fixtures .= 'app.' . Inflector::underscore(substr($file, 0, - 11)) . ',';
				}
			}
		}

		return substr($fixtures, 0, -1);
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser
			->description('Load test fixtures into any datasource you want')
			->addArgument('fixtures', array(
				'help' => 'A comma separated list of fixtures to use (Format is same as $fixtures property in CakeTest classes',
				'required' => false
			))
			->addOption('datasource', array(
				'short' => 'd',
				'help' => 'Datasource to use',
				'default' => 'default'
			))
			->addOption('plugin', array(
				'short' => 'p',
				'help' => 'Plugin to load fixtures from when not specifing fixtures manually',
				'default' => false
			));
	}
}
