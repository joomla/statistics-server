<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Repositories;

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
	 * Array containing the allowed sources
	 *
	 * @var  string[]
	 */
	public const ALLOWED_SOURCES = ['php_version', 'db_type', 'db_version', 'cms_version', 'server_os', 'cms_php_version', 'db_type_version'];

	/**
	 * The database driver.
	 *
	 * @var    DatabaseInterface
	 * @since  1.3.0
	 */
	private $db;

	/**
	 * Instantiate the repository.
	 *
	 * @param   DatabaseInterface  $db  The database driver.
	 */
	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Saves the given data.
	 *
	 * @param   \stdClass  $data  Data object to save.
	 *
	 * @return  void
	 */
	public function save(\stdClass $data): void
	{
		$writeApi = $this->db->createWriteApi();

		// Set the modified date of the record
		$timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();

		$pointTemplate = Point::measurement('joomla')
			->addTag('unique_id', $data->unique_id)
			->addField('source', "stats_server")
			->time($timestamp);

		$point = clone $pointTemplate;
		$point
			->addTag('php_version', $data->php_version)
			->addTag('db_type', $data->db_type)
			->addTag('db_version', $data->db_version)
			->addTag('cms_version', $data->cms_version)
			->addTag('server_os', $data->server_os);

		$writeApi->write($point, WritePrecision::S, 'statistics');

		// Extract major and minor version
		preg_match('/^(\d+)\.(\d+)\./', $data->cms_version, $matches);

		$point = clone $pointTemplate;
		$point
			->addTag('cms_version', $data->cms_version)
			->addTag('cms_major', $matches[1])
			->addTag('cms_minor', $matches[1] . '.' . $matches[2]);

		$writeApi->write($point, WritePrecision::S, 'cms');

        if (!empty($data->db_version)) {

            // Extract major and minor version
            preg_match('/^(\d+)\.(\d+)\./', $data->db_version, $matches);

            $dbServer = 'Unknown';

            if ($data->db_type === 'postgresql') {
                $dbServer = 'PostgreSQL';
            } elseif (str_contains($data->db_type, 'mysql')) {
                if (version_compare($data->db_version, '10.0.0', '>=')) {
                    $dbServer = 'MariaDB';
                } else {
                    $dbServer = 'MySQL';
                }
            } elseif (str_contains($data->db_type, 'sqlsrv')) {
                $dbServer = 'MSSQL';
            }

            $point = clone $pointTemplate;
            $point
                ->addTag('db_version', $data->db_version)
                ->addTag('db_driver', $data->db_type)
                ->addTag('db_server', $dbServer)
                ->addTag('db_major', $matches[1])
                ->addTag('db_minor', $matches[1] . '.' . $matches[2]);

            $writeApi->write($point, WritePrecision::S, 'database');
        }

        if (!empty($data->server_os)) {
            $os = explode(' ', $data->server_os, 2);

            $point = clone $pointTemplate;
            $point
                ->addTag('server_version', $data->server_os)
                ->addTag('server_os', $os[0]);

            $writeApi->write($point, WritePrecision::S, 'os');
        }

		// Extract major and minor version
		preg_match('/^(\d+)\.(\d+)\./', $data->php_version, $matches);

        if (!empty($data->php_version))
        {
            $point = clone $pointTemplate;
            $point
                ->addTag('php_version', $data->php_version)
                ->addTag('db_major', $matches[1])
                ->addTag('db_minor', $matches[1] . '.' . $matches[2]);
            $writeApi->write($point, WritePrecision::S, 'php');
        }
	}
}
