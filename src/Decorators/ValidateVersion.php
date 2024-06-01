<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Decorators;

/**
 * Decorator for objects which validate a version number.
 */
trait ValidateVersion
{
    /**
     * Validates and filters the version number
     *
     * @param   string  $version  The version string to validate.
     *
     * @return  string|boolean  A validated version number on success or boolean false.
     */
    protected function validateVersionNumber(string $version)
    {
        return preg_match('/\d+(?:\.\d+)+/', $version, $matches) ? $matches[0] : false;
    }
}
