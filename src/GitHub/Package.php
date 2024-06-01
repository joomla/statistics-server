<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\GitHub;

use Joomla\Github\Package as BasePackage;

/**
 * Extended GitHub API package class.
 */
abstract class Package extends BasePackage
{
    /**
     * Magic method to lazily create API objects
     *
     * @param   string  $name  Name of property to retrieve
     *
     * @return  Package  GitHub API package object.
     *
     * @throws  \InvalidArgumentException
     */
    public function __get($name)
    {
        $class = __NAMESPACE__ . '\\' . $this->package . '\\' . ucfirst($name);

        if (class_exists($class)) {
            if (!isset($this->$name)) {
                $this->$name = new $class($this->options, $this->client);
            }

            return $this->$name;
        }

        return parent::__get($name);
    }
}
