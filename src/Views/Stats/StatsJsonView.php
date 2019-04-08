<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Views\Stats;

use Joomla\StatsServer\Repositories\StatisticsRepository;
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
	private $dataSources = ['php_version', 'db_type', 'db_version', 'cms_version', 'server_os', 'cms_php_version', 'db_type_version'];

	/**
	 * Flag if the response should return the recently updated data.
	 *
	 * @var  boolean
	 */
	private $recent = false;

	/**
	 * Statistics repository.
	 *
	 * @var  StatisticsRepository
	 */
	private $repository;

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
	 * @param   StatisticsRepository  $repository  Statistics repository.
	 */
	public function __construct(StatisticsRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * Set whether the raw data should be returned.
	 *
	 * @param   bool  $authorizedRaw  Flag if the response should return the raw data.
	 *
	 * @return  void
	 */
	public function isAuthorizedRaw(bool $authorizedRaw): void
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
	public function isRecent(bool $recent): void
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
			$items = $this->repository->getRecentlyUpdatedItems();
		}
		else
		{
			$items = $this->repository->getItems($this->source);
		}

		if ($this->source)
		{
			return $this->processSingleSource($items);
		}

		$php_version     = [];
		$db_type         = [];
		$db_version      = [];
		$cms_version     = [];
		$server_os       = [];
		$cms_php_version = [];
		$db_type_version = [];

		// If we have the entire database, we have to loop within each group to put it all together
		foreach ($items as $group)
		{
			$this->totalItems = 0;

			foreach ($group as $item)
			{
				foreach ($this->dataSources as $source)
				{
					switch ($source)
					{
						case 'server_os':
							if (isset($item[$source]) && $item[$source] !== null)
							{
								// Special case, if the server is empty then change the key to "unknown"
								if (empty($item[$source]))
								{
									$item[$source] = 'unknown';
								}
								${$source}[$item[$source]] = $item['count'];
							}
							break;

						case 'cms_php_version':
							if ((isset($item['cms_version']) && $item['cms_version'] !== null) && (isset($item['php_version']) && $item['php_version'] !== null))
							{
								$index = $item['cms_version'] . ' - ' . $item['php_version'];
								$cms_php_version[$index] = $item['count'];
								$this->totalItems += $item['count'];								
							}
							break;

						case 'db_type_version':
							if ((isset($item['db_type']) && $item['db_type'] !== null) && (isset($item['db_version']) && $item['db_version'] !== null))
							{
								$index = $item['db_type'] . ' - ' . $item['db_version'];
								$db_type_version[$index] = $item['count'];
								$this->totalItems += $item['count'];								
							}
							break;

						default:
							if (isset($item[$source]) && $item[$source] !== null)
							{
								${$source}[$item[$source]] = $item['count'];
							}
							break;
					}
				}
			}
		}

		$data = [
			'php_version'     => $php_version,
			'db_type'         => $db_type,
			'db_version'      => $db_version,
			'cms_version'     => $cms_version,
			'server_os'       => $server_os,
			'cms_php_version' => $cms_php_version,
			'db_type_version' => $db_type_version,
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
	public function setSource(string $source): void
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
	private function buildResponseData(array $data): array
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
						'count' => $count,
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
	private function processSingleSource(array $items): string
	{
		$data = [
			${$this->source} = [],
		];

		$this->totalItems = 0;

		foreach ($items as $item)
		{
			switch ($this->source)
			{
				case 'server_os':
					// Special case, if the server is empty then change the key to "unknown"
					if (empty(trim($item[$this->source])))
					{
						$item[$this->source] = 'unknown';
					}
					$data[$this->source][$item[$this->source]] = $item['count'];
					break;

				case 'cms_php_version':
					$index = $item['cms_version'] . ' - ' . $item['php_version'];
					$data[$this->source][$index] = $item['count'];
					break;

				case 'db_type_version':
					$index = $item['db_type'] . ' - ' . $item['db_version'];
					$data[$this->source][$index] = $item['count'];
					break;

				default:
					$data[$this->source][$item[$this->source]] = $item['count'];
					break;
			}

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
	private function sanitizeData(array $responseData): array
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
						$version  = $exploded[0] . '.' . ($exploded[1] ?? '0');

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
				case 'cms_php_version':
				case 'db_type_version':
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
}
