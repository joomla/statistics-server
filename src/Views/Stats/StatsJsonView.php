<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Views\Stats;

use Joomla\StatsServer\Models\StatsModel;
use Joomla\View\BaseJsonView;

/**
 * JSON response for requesting the stats data.
 */
class StatsJsonView extends BaseJsonView
{
	/**
	 * Flag if the response should return the raw data.
	 *
	 * @var  boolean
	 */
	private $authorizedRaw = false;

	/**
	 * Array holding the valid data sources.
	 *
	 * @var  array
	 */
	private $dataSources = ['php_version', 'db_type', 'db_version', 'cms_version', 'server_os'];

	/**
	 * Flag if the response should return the recently updated data.
	 *
	 * @var  boolean
	 */
	private $recent = false;

	/**
	 * The data source to return.
	 *
	 * @var  string
	 */
	private $source = '';

	/**
	 * Count of the number of items.
	 *
	 * @var  integer
	 */
	private $totalItems = 0;

	/**
	 * Instantiate the view.
	 *
	 * @param   StatsModel  $model  The model object.
	 */
	public function __construct(StatsModel $model)
	{
		$this->model = $model;
	}

	/**
	 * Set whether the raw data should be returned.
	 *
	 * @param   bool  $authorizedRaw  Flag if the response should return the raw data.
	 *
	 * @return  void
	 */
	public function isAuthorizedRaw(bool $authorizedRaw)
	{
		$this->authorizedRaw = $authorizedRaw;
	}

	/**
	 * Set whether the recently updated data should be returned.
	 *
	 * @param   bool  $recent  Flag if the response should return the recently updated data.
	 *
	 * @return  void
	 */
	public function isRecent(bool $recent)
	{
		$this->recent = $recent;
	}

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 */
	public function render()
	{
		if ($this->recent)
		{
			$items = $this->model->getRecentlyUpdatedItems();
		}
		else
		{
			$items = $this->model->getItems($this->source);
		}

		// Null out the model now to free some memory
		$this->model = null;

		if ($this->source)
		{
			// The new combined API endPoint
			if ($this->source === 'cms_php')
			{
				return $this->processCombined($items);
			}

			return $this->processSingleSource($items);
		}

		$php_version = [];
		$db_type     = [];
		$db_version  = [];
		$cms_version = [];
		$server_os   = [];

		// If we have the entire database, we have to loop within each group to put it all together
		foreach ($items as $group)
		{
			$this->totalItems = 0;

			foreach ($group as $item)
			{
				foreach ($this->dataSources as $source)
				{
					if (isset($item[$source]) && !is_null($item[$source]))
					{
						// Special case, if the server is empty then change the key to "unknown"
						if ($source === 'server_os' && empty($item[$source]))
						{
							$item[$source] = 'unknown';
						}

						${$source}[$item[$source]] = $item['count'];

						$this->totalItems += $item['count'];
					}
				}
			}
		}

		$data = [
			'php_version' => $php_version,
			'db_type'     => $db_type,
			'db_version'  => $db_version,
			'cms_version' => $cms_version,
			'server_os'   => $server_os,
		];

		$responseData = $this->buildResponseData($data);

		$responseData['total'] = $this->totalItems;

		$this->addData('data', $responseData);

		return parent::render();
	}

	/**
	 * Set the data source.
	 *
	 * @param   string  $source  Data source to return.
	 *
	 * @return  void
	 */
	public function setSource(string $source)
	{
		$this->source = $source;
	}

	/**
	 * Process the raw data into the response data format.
	 *
	 * @param   array  $data  The raw data array.
	 *
	 * @return  array
	 */
	private function buildResponseData(array $data) : array
	{
		$responseData = [];

		foreach ($data as $key => $value)
		{
			foreach ($value as $name => $count)
			{
				if ($name)
				{
					$responseData[$key][] = [
						'name'  => $name,
						'count' => $count
					];
				}
			}
		}

		unset($data);

		if (!$this->authorizedRaw)
		{
			$responseData = $this->sanitizeData($responseData);
		}

		return $responseData;
	}

	/**
	 * Process the response for a single data source.
	 *
	 * @param   array  $items  The source items to process.
	 *
	 * @return  string  The rendered view.
	 */
	private function processSingleSource(array $items) : string
	{
		$data = [
			${$this->source} = [],
		];

		$this->totalItems = 0;

		foreach ($items as $item)
		{
			// Special case, if the server is empty then change the key to "unknown"
			if ($this->source === 'server_os' && empty(trim($item[$this->source])))
			{
				$item[$this->source] = 'unknown';
			}

			$data[$this->source][$item[$this->source]] = $item['count'];
			$this->totalItems += $item['count'];
		}

		$responseData = $this->buildResponseData($data);

		$responseData['total'] = $this->totalItems;

		$this->addData('data', $responseData);

		return parent::render();
	}

	/**
	 * Sanitize the response data into summarized groups.
	 *
	 * @param   array  $responseData  The response data to sanitize.
	 *
	 * @return  array
	 */
	private function sanitizeData(array $responseData) : array
	{
		foreach ($responseData as $key => $dataGroup)
		{
			switch ($key)
			{
				case 'php_version':
				case 'db_version':
				case 'cms_version':
					// We're going to group by minor version branch here and convert to a percentage
					$counts = [];

					foreach ($dataGroup as $row)
					{
						$exploded = explode('.', $row['name']);
						$version  = $exploded[0] . '.' . (isset($exploded[1]) ? $exploded[1] : '0');

						// If the container does not exist, add it
						if (!isset($counts[$version]))
						{
							$counts[$version] = 0;
						}

						$counts[$version] += $row['count'];
					}

					$sanitizedData = [];

					foreach ($counts as $version => $count)
					{
						$sanitizedData[$version] = round(($count / $this->totalItems) * 100, 2);
					}

					$responseData[$key] = $sanitizedData;

					break;

				case 'server_os':
					// We're going to group by operating system here
					$counts = [];

					foreach ($dataGroup as $row)
					{
						$fullOs = explode(' ', $row['name']);
						$os     = $fullOs[0];

						// If the container does not exist, add it
						if (!isset($counts[$os]))
						{
							$counts[$os] = 0;
						}

						$counts[$os] += $row['count'];
					}

					$sanitizedData = [];

					foreach ($counts as $os => $count)
					{
						$sanitizedData[$os] = round(($count / $this->totalItems) * 100, 2);
					}

					$responseData[$key] = $sanitizedData;

					break;

				case 'db_type':
				default:
					// For now, group by the object name and figure out the percentages
					$sanitizedData = [];

					foreach ($dataGroup as $row)
					{
						$sanitizedData[$row['name']] = round(($row['count'] / $this->totalItems) * 100, 2);
					}

					$responseData[$key] = $sanitizedData;

					break;
			}
		}

		return $responseData;
	}
	/**
	 * Process the response for a combined cms_php data source.
	 *
	 * @param   array  $items  The source items to process.
	 *
	 * @return  string  The rendered view.
	 */
	private function processCombined(array $items) : string
	{
		$data = [];

		$this->totalItems = 0;

		foreach ($items as $item)
		{
			$data[$this->source][$item['cms_version']][$item['php_version']]= $item['count'];
			$this->totalItems += $item['count'];
		}

		$data['total'] = $this->totalItems;

		$this->addData('data', $data);

		return parent::render();
	}
}
