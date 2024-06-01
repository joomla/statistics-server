<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Commands;

use Joomla\Console\Command\AbstractCommand;
use Joomla\StatsServer\Repositories\StatisticsRepository;
use Joomla\StatsServer\Views\Stats\StatsJsonView;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to take a record snapshot
 */
class SnapshotCommand extends AbstractCommand
{
    /**
     * The default command name
     *
     * @var  string|null
     */
    protected static $defaultName = 'snapshot';

    /**
     * JSON view for displaying the statistics.
     *
     * @var  StatsJsonView
     */
    private $view;

    /**
     * Filesystem adapter for the snapshots space.
     *
     * @var  Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param   StatsJsonView  $view        JSON view for displaying the statistics.
     * @param   Filesystem     $filesystem  Filesystem adapter for the snapshots space.
     */
    public function __construct(StatsJsonView $view, Filesystem $filesystem)
    {
        $this->view       = $view;
        $this->filesystem = $filesystem;

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

        $symfonyStyle->title('Creating Statistics Snapshot');

        // We want the full raw data set for our snapshot
        $this->view->isAuthorizedRaw(true);

        $source = $input->getOption('source');

        $filename = date('YmdHis');

        if ($source) {
            if (!\in_array($source, StatisticsRepository::ALLOWED_SOURCES)) {
                throw new InvalidOptionException(
                    \sprintf(
                        'Invalid source "%s" given, valid options are: %s',
                        $source,
                        implode(', ', StatisticsRepository::ALLOWED_SOURCES)
                    )
                );
            }

            $this->view->setSource($source);

            $filename .= '_' . $source;
        }

        if (!$this->filesystem->write($filename, $this->view->render())) {
            $symfonyStyle->error('Failed writing snapshot to the filesystem.');

            return 1;
        }

        $symfonyStyle->success('Snapshot recorded.');

        return 0;
    }

    /**
     * Configures the current command.
     *
     * @return  void
     */
    protected function configure(): void
    {
        $this->setDescription('Takes a snapshot of the statistics data.');
        $this->addOption(
            'source',
            null,
            InputOption::VALUE_OPTIONAL,
            'If given, filters the snapshot to a single source.'
        );
    }
}
