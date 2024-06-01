<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Commands\Database;

use Joomla\Console\Command\AbstractCommand;
use Joomla\StatsServer\Database\Migrations;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for checking the database migration status
 */
class MigrationStatusCommand extends AbstractCommand
{
    /**
     * The default command name
     *
     * @var  string|null
     */
    protected static $defaultName = 'database:migrations:status';

    /**
     * Database migrations helper
     *
     * @var  Migrations
     */
    private $migrations;

    /**
     * Constructor.
     *
     * @param   Migrations  $migrations  Database migrations helper
     */
    public function __construct(Migrations $migrations)
    {
        $this->migrations = $migrations;

        parent::__construct();
    }

    /**
     * Internal function to execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer  The command exit code
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $symfonyStyle->title('Database Migrations: Check Status');

        $status = $this->migrations->checkStatus();

        if (!$status->tableExists) {
            $symfonyStyle->comment('The migrations table does not exist, run the "database:migrate" command to set up the database.');
        } elseif ($status->latest) {
            $symfonyStyle->success('Your database is up-to-date.');
        } else {
            $symfonyStyle->comment(sprintf('Your database is not up-to-date. You are missing %d migration(s).', $status->missingMigrations));

            $symfonyStyle->table(
                [
                    'Current Version',
                    'Latest Version',
                ],
                [
                    [
                        $status->currentVersion,
                        $status->latestVersion,
                    ],
                ]
            );

            $symfonyStyle->comment('To update, run the "database:migrate" command.');
        }

        return 0;
    }

    /**
     * Configures the current command.
     *
     * @return  void
     */
    protected function configure(): void
    {
        $this->setDescription('Check the database migration status.');
    }
}
