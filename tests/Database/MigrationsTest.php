<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Tests\Database;

use Joomla\StatsServer\Database\Exception\UnknownMigrationException;
use Joomla\StatsServer\Database\Migrations;
use Joomla\StatsServer\Tests\DatabaseTestCase;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Test class for \Joomla\StatsServer\Database\Migrations
 */
class MigrationsTest extends DatabaseTestCase
{
	/**
	 * Migrations class for testing
	 *
	 * @var  Migrations
	 */
	private $migrations;

	/**
	 * This method is called before each test.
	 *
	 * @return  void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->migrations = new Migrations(
			static::$connection,
			new Filesystem(new Local(APPROOT . '/etc/migrations'))
		);
	}

	/**
	 * @testdox The migration status is checked without the table created
	 */
	public function testTheMigrationStatusIsCheckedWithoutTheTableCreated(): void
	{
		$status = $this->migrations->checkStatus();

		$this->assertFalse($status->tableExists);
	}

	/**
	 * @testdox The migration status is checked after executing the first migration
	 */
	public function testTheMigrationStatusIsCheckedAfterExecutingTheFirstMigration(): void
	{
		$this->migrations->migrateDatabase('20160618001');

		$status = $this->migrations->checkStatus();

		$this->assertTrue($status->tableExists);
		$this->assertSame('20160618001', $status->currentVersion);
		$this->assertGreaterThanOrEqual(1, $status->missingMigrations);
	}

	/**
	 * @testdox The migration status is checked after executing all migrations
	 */
	public function testTheMigrationStatusIsCheckedAfterExecutingAllMigrations(): void
	{
		$this->migrations->migrateDatabase();

		$status = $this->migrations->checkStatus();

		$this->assertTrue($status->tableExists);
		$this->assertTrue($status->latest);
		$this->assertSame(0, $status->missingMigrations);
	}

	/**
	 * @testdox Migrations fail with an unknown migration
	 */
	public function testMigrationsFailWithAnUnknownMigration(): void
	{
		$this->migrations->migrateDatabase();

		$this->expectException(UnknownMigrationException::class);

		$this->migrations->migrateDatabase('will-never-exist');
	}
}
