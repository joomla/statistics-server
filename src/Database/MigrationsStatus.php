<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Database;

/**
 * Data object representing the status of the database migrations
 */
class MigrationsStatus
{
    /**
     * The currently applied migration version
     *
     * @var  string|null
     */
    public $currentVersion;

    /**
     * Is the database at the latest version?
     *
     * @var  boolean
     */
    public $latest = false;

    /**
     * The newest available migration version
     *
     * @var  string|null
     */
    public $latestVersion;

    /**
     * Count of the number of missing migrations
     *
     * @var  integer
     */
    public $missingMigrations = 0;

    /**
     * Does the migrations table even exist?
     *
     * @var  boolean
     */
    public $tableExists = true;
}
