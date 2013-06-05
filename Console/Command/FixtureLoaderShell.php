<?php
App::uses('FixturizeFixtureManager', 'TestSuite/Fixture');
App::uses('CakeTestFixture', 'TestSuite/Fixture');
App::uses('ConnectionManager', 'Model');

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
		$CakeFixtureManager = new FixturizeFixtureManager();
		$CakeFixtureManager->loadAllFixtures($this->params['datasource'], explode(',', $this->args[0]));
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
				'required' => true
			))
			->addOption('datasource', array(
				'short' => 'd',
				'help' => 'Datasource to use',
				'default' => 'default'
			));
	}
}
