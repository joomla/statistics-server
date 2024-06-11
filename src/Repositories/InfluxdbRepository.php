<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Repositories;

use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Influxdb repository
 */
class InfluxdbRepository
{
    /**
     * The database driver.
     *
     * @var    Client
     * @since  1.3.0
     */
    private $db;

    /**
     * Instantiate the repository.
     *
     * @param Client $db The database driver.
     */
    public function __construct(Client $db)
    {
        $this->db = $db;
    }

    /**
     * Saves the given data.
     *
     * @param \stdClass $data Data object to save.
     *
     * @return  void
     */
    public function save(\stdClass $data): void
    {
        $writeApi = $this->db->createWriteApi();

        // Set the modified date of the record
        $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();

        $point = Point::measurement('joomla')
            ->addTag('unique_id', $data->unique_id)
            ->time($timestamp);

        // Extract major and minor version
        if (!empty($data->php_version)) {
            preg_match('/^(\d+)\.(\d+)\./', $data->php_version, $phpVersion);
            $point->addField('php_version', $data->php_version);
            if (!empty($phpVersion)) {
                $point->addField('php_major', $phpVersion[1])
                    ->addField('php_minor', $phpVersion[1] . '.' . $phpVersion[2]);
            }
        }

        // Prepare CMS version
        if (!empty($data->cms_version)) {
            preg_match('/^(\d+)\.(\d+)\./', $data->cms_version, $cmsVersions);

            $point->addField('cms_version', $data->cms_version);
            if (!empty($cmsVersions)) {
                $point
                    ->addField('cms_major', $cmsVersions[1])
                    ->addField('cms_minor', $cmsVersions[1] . '.' . $cmsVersions[2]);
            }
        }

        // Prepare Database versions
        if (!empty($data->db_version)) {
            preg_match('/^(\d+)\.(\d+)\./', $data->db_version, $dbVersions);

            $point->addField('db_version', $data->db_version);
            if (!empty($dbVersions)) {
                $point->addField('db_major', $dbVersions[1])
                    ->addField('db_minor', $dbVersions[1] . '.' . $dbVersions[2]);
            }
        }

        // Prepare Database Driver
        if (!empty($data->db_type)) {
            $dbServer = null;
            if ($data->db_type === 'postgresql') {
                $dbServer = 'PostgreSQL';
            } elseif (str_contains($data->db_type, 'mysql')) {
                $dbServer = 'MySQL';
                if (!empty($data->db_version)) {
                    if (
                        version_compare($data->db_version, '10.0.0', '>=')
                        // We know this is not 100% correct but more accurate than expecting MySQL with this version string
                        || version_compare($data->db_version, '5.5.5', '=')
                    ) {
                        $dbServer = 'MariaDB';
                    }
                }
            } elseif (str_contains($data->db_type, 'mariadb')) {
                $dbServer = 'MariaDB';
            } elseif (str_contains($data->db_type, 'sqlsrv')) {
                $dbServer = 'MSSQL';
            }

            $point->addField('db_driver', $data->db_type);
            if (!empty($dbServer)) {
                $point->addField('db_server', $dbServer);
            }
        }

        // Prepare Operating System
        if (!empty($data->server_os)) {
            $os = explode(' ', $data->server_os, 2);

            $point->addField('server_string', $data->server_os);
            if (!empty($os[0])) {
                $point->addField('server_os', $os[0]);
            }
        }

        $writeApi->write($point, \InfluxDB2\Model\WritePrecision::S, 'cms');
    }
}
