# CakePHP Fixturize Plugin #

Managing fixtures is arguably the most difficult and boring process of unit testing in CakePHP.
When you start a fresh project it's quite easy to make your fixtures progress along with your code, as the
amount of changes are usually small. But once your applications reaches a certain point, it's actually quite hard to
implement new features, as changing features would consume a great deal of your time.

Another case where handling fixtures is a daunting task is when trying to test an existing project with no
previous tests. Generating the initial set of fixtures is hard as selecting the data that is relevant to the
features you want to test.

This plugin allows you to import queries expressed in pure SQL, either as files or by importing directly from
a seed database. This helps you use the tools you want for running migrations of your schema, or manipulate your
data using SQL so it can be imported again.

Additionally, it provides a console shell to load your existing fixture files in a target database connection, so you
can migrate any existing code you have to SQL managed fixtures.

## Requirements ##

* CakePHP 2.x
* MySQL

## Installation ##

There are a few ways to choose from for installing this plugin:

_[Composer]_

Add the following to your composer.json in the corresponding configuration keys:

	{
		"extra": {
			"installer-paths": {
				"Plugin/Fixturize": ["lorenzo/cakephp-fixturize"]
		}
	},
		"require" : {
			"lorenzo/cakephp-fixturize": "master"
		}
	}


_[Manual]_

* Download this: [https://github.com/lorenzo/cakephp-fixturize/zipball/master](https://github.com/lorenzo/cakephp-fixturize/zipball/master)
* Unzip that download.
* Copy the resulting folder to `app/Plugin`
* Rename the folder you just copied to `Fixturize`

_[GIT Submodule]_

In your app directory type:

	git submodule add git@github.com:lorenzo/cakephp-fixturize.git app/Plugin/Fixturize
	git submodule init
	git submodule update

_[GIT Clone]_

In your plugin directory type:

	git clone git://github.com/lorenzo/cakephp-fixturize.git app/Plugin/Fixturize

### Enable plugin

Enable the plugin your `app/Config/bootstrap.php` file:

```php
    CakePlugin::load('Fixturize');
```

## Usage

You can use this plugin in multiple ways, but typically, you'll want to start by importing an existing set of fixtures
into a test database.

### Load existing fixtures into a target connection

If you need to load your existing PHP based fixtures into a database (either for migrating them to a SQL based version or for quick visualization)
then execute this command in the console:

	./Console/cake Fixturize.fixture_loader app.event,app.tag,app.category --datasource test

It will load the comma separated list of fixtures schema and data into the datasource 'test'.

### Load all fixtures from app or plugin

Instead of typing all the fixtures you need you can also simply not specify and, the shell will load all fixtures from the app then

	./Console/cake Fixturize.fixture_loader --datasource test

To load all fixtures from a plugin you'll have to specify the plugin as well

	./Console/cake Fixturize.fixture_loader --plugin SomePluginName --datasource test

### Loading your fixtures from SQL files

When your amount of data is manageable, it's a good option to load it directly from SQL files that can be migrated, dumped again and
managed with a versioning system like GIT.

Fixture SQL files can contain the table creation statement, any alter tables (for example foreign keys) and data inserts. But you can also
manage the schema or the records via the `$fields` and `$records` property in your fixture as you would normally do if you define them in the 
fixture class.

If you choose to have the schema creation statements in the SQL file, make sure the CREATE statement contains `IF NOT EXISTS`.

Files should be stored in `app/Test/Fixture/SQL/` or `app/YourPlugin/Test/Fixture/SQL` and have the .sql extension.

Example:

```php
	<?php

	App::uses('SQLTestFixture', 'Fixturize.TestSuite/Fixture');

	/**
	 * CategoryFixture
	 *
	 */
	class CategoryFixture extends SQLTestFixture {

		public $plugin = 'MyPlugin'; // Can be ommited if the sql file is locate in app

		public $file = 'overriding_file_name.sql'; // By default it would use categories.sql
	}
```

And that's all you need!

### Loading your fixtures directly from a database

When the amount of data increases, you might consider having all your fixture data directly in a database, so you can copy schema and
data directly from it before running each test. This is also considerably faster than loading fixture from a SQL file.

This requires creating a new database config in `app/Config/database.php` to connect to the seed database (the one containing the test data):

```php
	public $test_seed = array(
		'datasource' => 'Database/Mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'root',
		'password' => 'root',
		'database' => 'test_seed',
		'prefix' => '',
		'encoding' => 'utf8',
	);
```

**Warning**

Please ensure that you also have a `$test` database config that is pointing to a separate database.

`$test_seed` contains the seed data whereas `$test` is an empty database where the CakePHP Test logic will use to run queries on.

This is an example of using the import data fixture class:

```php
	<?php

	App::uses('TableCopyTestFixture', 'Fixturize.TestSuite/Fixture');

	/**
	 * CategoryFixture
	 *
	 */
	class CategoryFixture extends TableCopyTestFixture {

	}
```

Yes, as easy as that!
