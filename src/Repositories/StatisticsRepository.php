<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Repositories;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Statistics repository
 */
class StatisticsRepository
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
	 * Loads the statistics data from the database.
	 *
	 * @param   string  $column  A single column to filter on
	 *
	 * @return  array  An array containing the response data
	 *
	 * @throws  \InvalidArgumentException
	 */
	public function getItems(string $column = ''): array
	{
		// Validate the requested column is actually in the table
		if ($column !== '')
		{
			// The column should exist in the table and be part of the API
			if (!\in_array($column, self::ALLOWED_SOURCES))
			{
				throw new \InvalidArgumentException('An invalid data source was requested.', 404);
			}

			return $this->db->setQuery(
				$this->db->getQuery(true)
					->select('*')
					->from($this->db->quoteName('#__jstats_counter_' . $column))
			)->loadAssocList();
		}

		$return = [];

		foreach (self::ALLOWED_SOURCES as $column)
		{
			$return[$column] = $this->db->setQuery(
				$this->db->getQuery(true)
					->select('*')
					->from($this->db->quoteName('#__jstats_counter_' . $column))
			)->loadAssocList();
		}

		return $return;
	}

	/**
	 * Loads the recently updated statistics data from the database.
	 *
	 * Recently updated is an arbitrary 90 days, submit a pull request for a different behavior.
	 *
	 * @return  array  An array containing the response data
	 */
	public function getRecentlyUpdatedItems(): array
	{
		$return = [];

		foreach (self::ALLOWED_SOURCES as $column)
		{
			if (($column !== 'cms_php_version') && ($column !== 'db_type_version'))
			{
				$return[$column] = $this->db->setQuery(
					$this->db->getQuery(true)
						->select($column)
						->select('COUNT(' . $column . ') AS count')
						->from($this->db->quoteName('#__jstats'))
						->where('modified BETWEEN DATE_SUB(NOW(), INTERVAL 90 DAY) AND NOW()')
						->group($column)
				)->loadAssocList();
				continue;
			}

			if ($column === 'cms_php_version')
			{
				$return['cms_php_version'] = $this->db->setQuery(
					$this->db->getQuery(true)
						->select('CONCAT(' . $this->db->quoteName('cms_version') . ', ' . $this->db->quote(' - ') . ', ' . $this->db->quoteName('php_version') . ') AS cms_php_version')
						->select('COUNT(*) AS count')
						->from($this->db->quoteName('#__jstats'))
						->where('modified BETWEEN DATE_SUB(NOW(), INTERVAL 90 DAY) AND NOW()')
						->group('CONCAT(' . $this->db->quoteName('cms_version') . ', ' . $this->db->quote(' - ') . ', ' . $this->db->quoteName('php_version') . ')')
				)->loadAssocList();
				continue;
			}

			if ($column === 'db_type_version')
			{
				$return['db_type_version'] = $this->db->setQuery(
					$this->db->getQuery(true)
						->select('CONCAT(' . $this->db->quoteName('db_type') . ', ' . $this->db->quote(' - ') . ', ' . $this->db->quoteName('db_version') . ') AS db_type_version')
						->select('COUNT(*) AS count')
						->from($this->db->quoteName('#__jstats'))
						->where('modified BETWEEN DATE_SUB(NOW(), INTERVAL 90 DAY) AND NOW()')
						->group('CONCAT(' . $this->db->quoteName('db_type') . ', ' . $this->db->quote(' - ') . ', ' . $this->db->quoteName('db_version') . ')')
				)->loadAssocList();
				continue;
			}
		}

		return $return;
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
		// Set the modified date of the record
		$data->modified = (new \DateTime('now', new \DateTimeZone('UTC')))->format($this->db->getDateFormat());

		// Check if a row exists for this unique ID and update the existing record if so
		$recordExists = $this->db->setQuery(
			$this->db->getQuery(true)
				->select('unique_id')
				->from('#__jstats')
				->where('unique_id = :unique_id')
				->bind('unique_id', $data->unique_id, ParameterType::STRING)
		)->loadResult();

		if ($recordExists)
		{
			$this->db->updateObject('#__jstats', $data, ['unique_id']);
		}
		else
		{
			$this->db->insertObject('#__jstats', $data, ['unique_id']);
		}
	}
}
