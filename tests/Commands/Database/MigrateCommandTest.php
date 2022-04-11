<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Tests\Commands\Database;

use Joomla\Console\Application;
use Joomla\StatsServer\Commands\Database\MigrateCommand;
use Joomla\StatsServer\Database\Migrations;
use League\Flysystem\FileNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test class for \Joomla\StatsServer\Commands\Database\MigrateCommand
 */
class MigrateCommandTest extends TestCase
{
	/**
	 * @testdox The command executes database migrations
	 */
	public function testTheCommandExecutesDatabaseMigrations(): void
	{
		/** @var MockObject|Migrations $migrations */
		$migrations = $this->createMock(Migrations::class);
		$migrations->expects($this->once())
			->method('migrateDatabase');

		$logger = new TestLogger;

		$input  = new ArrayInput(
			[
				'command' => 'database:migrate',
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new MigrateCommand($migrations);
		$command->setApplication($application);
		$command->setLogger($logger);

		$this->assertSame(0, $command->execute($input, $output));

		$screenOutput = $output->fetch();

		$this->assertStringContainsString('Database migrated to latest version.', $screenOutput);
	}

	/**
	 * @testdox The command executes the given database migration
	 */
	public function testTheCommandExecutesTheGivenDatabaseMigration(): void
	{
		/** @var MockObject|Migrations $migrations */
		$migrations = $this->createMock(Migrations::class);
		$migrations->expects($this->once())
			->method('migrateDatabase')
			->with('abc123');

		$logger = new TestLogger;

		$input  = new ArrayInput(
			[
				'command'   => 'database:migrate',
				'--mversion' => 'abc123',
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new MigrateCommand($migrations);
		$command->setApplication($application);
		$command->setLogger($logger);

		$this->assertSame(0, $command->execute($input, $output));

		$screenOutput = $output->fetch();

		$this->assertStringContainsString('Database migrated to version "abc123".', $screenOutput);
	}

	/**
	 * @testdox The command handles migration errors
	 */
	public function testTheCommandHandlesMigrationErrors(): void
	{
		/** @var MockObject|Migrations $migrations */
		$migrations = $this->createMock(Migrations::class);
		$migrations->expects($this->once())
			->method('migrateDatabase')
			->willThrowException(new FileNotFoundException('abc123.sql'));

		$logger = new TestLogger;

		$input  = new ArrayInput(
			[
				'command' => 'database:migrate',
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new MigrateCommand($migrations);
		$command->setApplication($application);
		$command->setLogger($logger);

		$this->assertSame(1, $command->execute($input, $output));

		$screenOutput = $output->fetch();

		$this->assertStringContainsString('Error migrating database: File not found at path: abc123.sql', $screenOutput);
	}
}
