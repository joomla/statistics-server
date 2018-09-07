<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Models;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\Query\LimitableInterface;
use Joomla\Model\{
	DatabaseModelInterface, DatabaseModelTrait
};

/**
 * Statistics database model
 */
class StatsModel implements DatabaseModelInterface
{
	use DatabaseModelTrait;

	/**
	 * Instantiate the model.
	 *
	 * @param   DatabaseDriver  $db  The database driver.
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->setDb($db);
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
	public function getItems(string $column = '') : array
	{
		$db         = $this->getDb();
		$query      = $db->getQuery(true);
		$columnList = $db->getTableColumns('#__jstats');

		// The new endPoint
		if ($column === 'db_type_version')
		{
			return $db->setQuery(
				$query
					->select('*')
					->from($db->quoteName('#__jstats_counter_db_type_version'))
			)->loadAssocList();
		}

		// Validate the requested column is actually in the table
		if ($column !== '')
		{
			// The column should exist in the table and be part of the API
			if (!in_array($column, array_keys($columnList)) && !in_array($column, ['unique_id', 'modified']))
			{
				throw new \InvalidArgumentException('An invalid data source was requested.', 404);
			}

			return $db->setQuery(
				$query
					->select('*')
					->from($db->quoteName('#__jstats_counter_' . $column))
			)->loadAssocList();
		}

		$return = [];

		foreach (array_keys($columnList) as $column)
		{
			// The column should exist in the table and be part of the API
			if (in_array($column, ['unique_id', 'modified']))
			{
				continue;
			}

			$return[$column] = $db->setQuery(
				$query->clear()
					->select('*')
					->from($db->quoteName('#__jstats_counter_' . $column))
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
	public function getRecentlyUpdatedItems() : array
	{
		$db         = $this->getDb();
		$columnList = $db->getTableColumns('#__jstats');

		$return = [];

		foreach (array_keys($columnList) as $column)
		{
			// The column should exist in the table and be part of the API
			if (in_array($column, ['unique_id', 'modified']))
			{
				continue;
			}

			$query = $db->getQuery(true);

			$return[$column] = $db->setQuery(
				$query->select($column)
					->select('COUNT(' . $column . ') AS count')
					->from($db->quoteName('#__jstats'))
					->where('modified BETWEEN DATE_SUB(NOW(), INTERVAL 90 DAY) AND NOW()')
					->group($column)
			)->loadAssocList();
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
	public function save(\stdClass $data)
	{
		$db = $this->getDb();

		// Set the modified date of the record
		$data->modified = (new \DateTime('now', new \DateTimeZone('UTC')))->format($db->getDateFormat());

		// Check if a row exists for this unique ID and update the existing record if so
		$recordExists = $db->setQuery(
			$db->getQuery(true)
				->select('unique_id')
				->from('#__jstats')
				->where('unique_id = :unique_id')
				->bind('unique_id', $data->unique_id, \PDO::PARAM_STR)
		)->loadResult();

		if ($recordExists)
		{
			$db->updateObject('#__jstats', $data, ['unique_id']);
		}
		else
		{
			$db->insertObject('#__jstats', $data, ['unique_id']);
		}
	}
}
