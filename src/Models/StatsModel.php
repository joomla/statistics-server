<?php

namespace Stats\Models;

use Joomla\Database\Query\LimitableInterface;
use Joomla\Model\AbstractDatabaseModel;

/**
 * Statistics database model
 *
 * @since  1.0
 */
class StatsModel extends AbstractDatabaseModel
{
	/**
	 * The query batch size
	 *
	 * @var    integer
	 * @since  1.0
	 */
	private $batchSize = 25000;

	/**
	 * Loads the statistics data from the database.
	 *
	 * @param   string  $column  A single column to filter on
	 *
	 * @return  \array[]  Array of data arrays.
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function getItems($column = null)
	{
		$db = $this->getDb();

		// Validate the requested column is actually in the table
		if ($column !== null)
		{
			switch ($column)
			{
				case 'php_version':
				case 'db_version':
				case 'db_type':
				case 'cms_version':
				case 'server_os':
					return $db->setQuery(
						$db->getQuery(true)
							->select('*')
							->from('#__jstats_counter_' . $column)
					)->loadAssocList($column);
					break;

				default:
					throw new \InvalidArgumentException('An invalid data source was requested.', 404);
			}
		}

		// If fetching all data from the table, we need to break this down a fair bit otherwise we're going to run out of memory
		$totalRecords = $db->setQuery(
			$db->getQuery(true)
				->select('COUNT(unique_id)')
				->from('#__jstats')
		)->loadResult();

		$return = [];

		$query = $db->getQuery(true)
			->select(['php_version', 'db_type', 'db_version', 'cms_version', 'server_os'])
			->from('#__jstats')
			->group('unique_id');

		$limitable = $query instanceof LimitableInterface;

		// We can't have this as a single array, we run out of memory... This is gonna get interesting...
		for ($offset = 0; $offset < $totalRecords; $offset + $this->batchSize)
		{
			if ($limitable)
			{
				$query->setLimit($this->batchSize, $offset);

				$db->setQuery($query);
			}
			else
			{
				$db->setQuery($query, $offset, $this->batchSize);
			}

			$return[] = $db->loadAssocList();

			$offset += $this->batchSize;
		}

		// Disconnect the DB to free some memory
		$db->disconnect();

		// And unset some variables
		unset($db, $query, $offset, $totalRecords);

		return $return;
	}

	/**
	 * Saves the given data.
	 *
	 * @param   \stdClass  $data  Data object to save.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function save($data)
	{
		$db = $this->getDb();

		// Set the modified date of the record
		$data->modified = (new \DateTime('now', new \DateTimeZone('UTC')))->format($db->getDateFormat());

		// Check if a row exists for this unique ID and update the existing record if so
		$recordExists = $db->setQuery(
			$db->getQuery(true)
				->select('unique_id')
				->from('#__jstats')
				->where('unique_id = ' . $db->quote($data->unique_id))
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
