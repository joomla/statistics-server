<?php

namespace Stats\Controllers;

use Joomla\Controller\AbstractController;
use Stats\Models\StatsModel;

/**
 * Controller for processing submitted statistics data.
 *
 * @method         \Stats\Application  getApplication()  Get the application object.
 * @property-read  \Stats\Application  $app              Application object
 *
 * @since          1.0
 */
class SubmitControllerCreate extends AbstractController
{
	/**
	 * Statistics model object.
	 *
	 * @var    StatsModel
	 * @since  1.0
	 */
	private $model;

	/**
	 * Allowed Databse Types
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $databaseTypes = array(
			'mysqli',
			'mysql',
			'postgresql',
			'pdomysql',
			'sqlazure'
		);

	/**
	 * Constructor.
	 *
	 * @param   StatsModel  $model  Statistics model object.
	 *
	 * @since   1.0
	 */
	public function __construct(StatsModel $model)
	{
		$this->model = $model;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		$input = $this->getInput();

		$data = [
			'php_version' => $input->getRaw('php_version'),
			'db_version'  => $input->getRaw('db_version'),
			'cms_version' => $input->getRaw('cms_version')
		];

		// Filter the submitted version data.
		$data = array_map(
			function ($value)
			{
				return preg_match('/\d+(?:\.\d+)+/', $value, $matches) ? $matches[0] : $value;
			},
			$data
		);

		$data['unique_id'] = $input->getString('unique_id');
		$data['db_type']   = $input->getString('db_type');
		$data['server_os'] = $input->getString('server_os');

		// Perform some checks
		$data['cms_version'] = $this->checkCMSVersion($data['cms_version']);
		$data['db_type']     = $this->checkDatabaseType($data['db_type']);

		// We require at a minimum a unique ID and the CMS version
		if (empty($data['unique_id']) || empty($data['cms_version']))
		{
			$this->getApplication()->getLogger()->info(
				'Missing required data from request.',
				['postData' => $data]
			);

			throw new \RuntimeException('There was an error storing the data.', 401);
		}

		// We have checked some values if one of them is false reject the input
		if (($data['cms_version'] == false) || ($data['db_type'] == false))
		{
			$this->getApplication()->getLogger()->info(
				"The request don't pass the tests",
				['postData' => $data]
			);

			throw new \RuntimeException('There was an error in the data.', 401);
		}

		$this->model->save((object) $data);

		$response = [
			'error'   => false,
			'message' => 'Data saved successfully'
		];

		$this->getApplication()->setBody(json_encode($response));

		return true;
	}

	/**
	 * Check the CMS Version
	 *
	 * @return  false on failiure else the CMS Version
	 *
	 * @since   1.0
	 */
	private function checkCMSVersion($data)
	{
		$dotchecks = explode('.', $data);

		// We ever use 2 dots and 3 parts in our CMS Version
		if (count($dotchecks) != 3)
		{
			return false;
		}

		// The pulugin is installed since 3.5.0 other CMS Versions can't have this plugin installed
		if (version_compare($data, '3.5.0', '<'))
		{
			return false;
		}

		// Joomla 4 is not released skip it.
		if (version_compare($data, '4.0.0', '>='))
		{
			return false;
		}
		
		return $data;
	}

	/**
	 * Check the Database type
	 *
	 * @return  false on failiure else the Database type
	 *
	 * @since   1.0
	 */
	private function checkDatabaseType($data)
	{
		if (!in_array($data, $databaseTypes))
		{
			return false;
		}
		
		return $data;
	}
}
