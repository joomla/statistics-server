<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\GitHub;

use Joomla\Github\Github as JGitHub;

/**
 * Extended GitHub API object.
 *
 * @property-read  Package\Repositories   $repositories   GitHub API object for the repositories package.
 */
class GitHub extends JGitHub
{
    /**
     * Magic method to lazily create API objects
     *
     * @param   string  $name  Name of property to retrieve
     *
     * @return  \Joomla\Github\AbstractGithubObject  GitHub API object (gists, issues, pulls, etc).
     *
     * @throws  \InvalidArgumentException If $name is not a valid sub class.
     */
    public function __get($name)
    {
        $class = __NAMESPACE__ . '\\Package\\' . ucfirst($name);

        if (class_exists($class)) {
            if (!isset($this->$name)) {
                $this->$name = new $class($this->options, $this->client);
            }

            return $this->$name;
        }

        return parent::__get($name);
    }
}
