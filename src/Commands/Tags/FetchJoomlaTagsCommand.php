<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Commands\Tags;

use Joomla\StatsServer\Decorators\ValidateVersion;
use Joomla\StatsServer\GitHub\GitHub;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for processing tags for the Joomla! CMS from GitHub
 */
class FetchJoomlaTagsCommand extends AbstractTagCommand
{
    use ValidateVersion;

    /**
     * The default command name
     *
     * @var  string|null
     */
    protected static $defaultName = 'tags:joomla';

    /**
     * Constructor.
     *
     * @param   GitHub      $github      GitHub API object.
     * @param   Filesystem  $filesystem  Filesystem adapter for the versions space.
     */
    public function __construct(GitHub $github, Filesystem $filesystem)
    {
        parent::__construct($github, $filesystem);

        $this->repoName  = 'joomla-cms';
        $this->repoOwner = 'joomla';
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
        $this->io->title('Fetching Joomla Releases');

        $versions    = [];
        $highVersion = '0.0.0';

        foreach ($this->getTags() as $tag) {
            $version = $this->validateVersionNumber($tag->name);

            // Only process if the tag name looks like a version number
            if ($version === false) {
                continue;
            }

            // Joomla only uses major.minor.patch so everything else is invalid
            $explodedVersion = explode('.', $version);

            if (\count($explodedVersion) != 3) {
                continue;
            }

            // Version collection is valid for the 3.x series and later
            if (version_compare($version, '3.0.0', '<')) {
                continue;
            }

            // We have a valid version number, great news... add it to our array if it isn't already present
            if (!\in_array($version, $versions)) {
                $versions[] = $version;

                // If this version is higher than our high version, replace it
                // TODO - When 4.0 is stable adjust this logic
                if (version_compare($version, '4.0', '<') && version_compare($version, $highVersion, '>')) {
                    $highVersion = $version;
                }
            }
        }

        // If the high version is not the default then let's add some (arbitrary) allowed versions based on the repo's dev structure
        if ($highVersion !== '0.0.0') {
            $explodedVersion = explode('.', $highVersion);

            // Allow the next patch release after this one
            $nextPatch  = $explodedVersion[2] + 1;
            $versions[] = $explodedVersion[0] . '.' . $explodedVersion[1] . '.' . $nextPatch;

            // And allow the next minor release after this one
            $nextMinor  = $explodedVersion[1] + 1;
            $versions[] = $explodedVersion[0] . '.' . $nextMinor . '.0';
        }

        if (!$this->filesystem->put('joomla.json', json_encode($versions))) {
            $this->io->error('Failed writing version data to the filesystem.');

            return 1;
        }

        $this->io->success('Joomla! versions updated.');

        return 0;
    }

    /**
     * Configures the current command.
     *
     * @return  void
     */
    protected function configure(): void
    {
        $this->setDescription('Parses the release tags for the Joomla! CMS GitHub repository.');
    }
}
