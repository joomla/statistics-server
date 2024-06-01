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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for migrating the database
 */
class MigrateCommand extends AbstractCommand implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The default command name
     *
     * @var  string|null
     */
    protected static $defaultName = 'database:migrate';

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

        $symfonyStyle->title('Database Migrations: Migrate');

        // If a version is given, we are only executing that migration
        $version = $input->getOption('mversion');

        try {
            $this->migrations->migrateDatabase($version);
        } catch (\Exception $exception) {
            $this->logger->critical(
                'Error migrating database',
                ['exception' => $exception]
            );

            $symfonyStyle->error(sprintf('Error migrating database: %s', $exception->getMessage()));

            return 1;
        }

        if ($version) {
            $message = sprintf('Database migrated to version "%s".', $version);
        } else {
            $message = 'Database migrated to latest version.';
        }

        $this->logger->info($message);

        $symfonyStyle->success($message);

        return 0;
    }

    /**
     * Configures the current command.
     *
     * @return  void
     */
    protected function configure(): void
    {
        $this->setDescription('Migrate the database schema to a newer version.');
        $this->addOption(
            'mversion',
            null,
            InputOption::VALUE_OPTIONAL,
            'If specified, only the given migration will be executed if necessary.'
        );
    }
}
