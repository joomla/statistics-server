<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Database\Exception;

use League\Flysystem\FileNotFoundException;

/**
 * Exception indicating a migration is not known
 */
class UnknownMigrationException extends FileNotFoundException
{
}
