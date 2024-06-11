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
 * Command for processing tags for the PHP project from GitHub
 */
class FetchPhpTagsCommand extends AbstractTagCommand
{
    use ValidateVersion;

    /**
     * The default command name
     *
     * @var  string|null
     */
    protected static $defaultName = 'tags:php';

    /**
     * Constructor.
     *
     * @param   GitHub      $github      GitHub API object.
     * @param   Filesystem  $filesystem  Filesystem adapter for the versions space.
     */
    public function __construct(GitHub $github, Filesystem $filesystem)
    {
        parent::__construct($github, $filesystem);

        $this->repoName  = 'php-src';
        $this->repoOwner = 'php';
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
        $this->io->title('Fetching PHP Releases');

        $versions          = [];
        $supportedBranches = [
            '7.2' => '',
            '7.3' => '',
            '7.4' => '',
        ];

        foreach ($this->getTags() as $tag) {
            // Replace 'php-' from the tag to get our version number; skip if the segment doesn't exist
            if (strpos($tag->name, 'php-') !== 0) {
                continue;
            }

            $version = substr($tag->name, 4);

            $version = $this->validateVersionNumber($version);

            // Only process if the tag name looks like a version number
            if ($version === false) {
                continue;
            }

            // We only track versions based on major.minor.patch so everything else is invalid
            $explodedVersion = explode('.', $version);

            if (\count($explodedVersion) != 3) {
                continue;
            }

            // Joomla collects stats for the 3.x branch and later, the minimum PHP version for 3.0.0 was 5.3.1
            if (version_compare($version, '5.3.1', '<')) {
                continue;
            }

            // We have a valid version number, great news... add it to our array if it isn't already present
            if (!\in_array($version, $versions)) {
                $versions[] = $version;

                // If this version is higher than our branch's high version, replace it
                $branch = substr($version, 0, 3);

                if (isset($supportedBranches[$branch]) && version_compare($version, $supportedBranches[$branch], '>')) {
                    $supportedBranches[$branch] = $version;
                }
            }
        }

        // For each supported branch, also add the next patch release
        foreach ($supportedBranches as $branch => $version) {
            $explodedVersion = explode('.', $version);

            $nextPatch  = $explodedVersion[2] + 1;
            $versions[] = $explodedVersion[0] . '.' . $explodedVersion[1] . '.' . $nextPatch;
        }

        // Use $branch from the previous loop to allow the next minor version (PHP's master branch)
        $explodedVersion = explode('.', $branch);

        $nextMinor   = $explodedVersion[1] + 1;
        $nextRelease = $explodedVersion[0] . '.' . $nextMinor . '.0';

        // There won't be a PHP 7.5, change next release to 8.0 if needed
        if ($nextRelease === '7.5.0') {
            $nextRelease = '8.0.0';
        }

        if (!\in_array($nextRelease, $versions, true)) {
            $versions[] = $nextRelease;
        }

        if (!$this->filesystem->put('php.json', json_encode($versions))) {
            $this->io->error('Failed writing version data to the filesystem.');

            return 1;
        }

        $this->io->success('PHP versions updated.');

        return 0;
    }

    /**
     * Configures the current command.
     *
     * @return  void
     */
    protected function configure(): void
    {
        $this->setDescription('Parses the release tags for the PHP GitHub repository.');
    }
}
