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
	 * Allowed Database Types.
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $databaseTypes = [
		'mysql',
		'mysqli',
		'pdomysql',
		'postgresql',
		'sqlazure',
		'sqlsrv',
	];

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
			'cms_version' => $input->getRaw('cms_version'),
			'unique_id'   => $input->getString('unique_id'),
			'db_type'     => $input->getString('db_type'),
			'server_os'   => $input->getString('server_os')
		];

		// Backup the original POST before manipulating/validating data
		$originalData = $data;

		// Validate the submitted data
		$data['php_version'] = $this->checkPHPVersion($data['php_version']);
		$data['cms_version'] = $this->checkCMSVersion($data['cms_version']);
		$data['db_type']     = $this->checkDatabaseType($data['db_type']);
		$data['db_version']  = $this->validateVersionNumber($data['db_version']);

		// We require at a minimum a unique ID and the CMS version
		if (empty($data['unique_id']) || empty($data['cms_version']))
		{
			$this->getApplication()->getLogger()->info(
				'Missing required data from request.',
				['postData' => $originalData]
			);

			throw new \RuntimeException('There was an error storing the data.', 401);
		}

		// If the below data does not pass tests, we do not accept the POST
		if ($data['php_version'] === false || $data['cms_version'] === false || $data['db_type'] === false || $data['db_version'] === false)
		{
			$this->getApplication()->getLogger()->info(
				'The request data is invalid.',
				['postData' => $originalData]
			);

			throw new \RuntimeException('Invalid data submission.', 401);
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
	 * Check the CMS version.
	 *
	 * @param   string  $version  The version number to check.
	 *
	 * @return  string|boolean  The version number on success or boolean false on failure.
	 *
	 * @since   1.0
	 */
	private function checkCMSVersion($version)
	{
		$version = $this->validateVersionNumber($version);

		// If the version number is invalid, don't go any further
		if ($version === false)
		{
			return false;
		}

		// We are only collecting data for the 3.x series
		if (version_compare($version, '3.0.0', '<') || version_compare($version, '4.0.0', '>='))
		{
			return false;
		}

		return $version;
	}

	/**
	 * Check the database type
	 *
	 * @param   string  $database  The database type to check.
	 *
	 * @return  string|boolean  The database type on success or boolean false on failure.
	 *
	 * @since   1.0
	 */
	private function checkDatabaseType($database)
	{
		if (!in_array($database, $this->databaseTypes))
		{
			return false;
		}

		return $database;
	}

	/**
	 * Check the PHP version
	 *
	 * @param   string  $version  The version number to check.
	 *
	 * @return  string|boolean  The version number on success or boolean false on failure.
	 *
	 * @since   1.0
	 */
	private function checkPHPVersion($version)
	{
		$version = $this->validateVersionNumber($version);

		// If the version number is invalid, don't go any further
		if ($version === false)
		{
			return false;
		}

		// The version string should meet the minimum supported PHP version for 3.0.0 and be a released PHP version
		if (version_compare($version, '5.3.1', '<') || version_compare($version, '8.0.0', '>='))
		{
			return false;
		}

		if (version_compare($version, '6.0.0', '>=') && version_compare($version, '7.0.0', '<'))
		{
			return false;
		}

		return $version;
	}

	/**
	 * Validates and filters the version number
	 *
	 * @param   string  $version  The version string to validate.
	 *
	 * @return  string|boolean  A validated version number on success or boolean false.
	 *
	 * @since   1.0
	 */
	private function validateVersionNumber($version)
	{
		return preg_match('/\d+(?:\.\d+)+/', $version, $matches) ? $matches[0] : false;
	}
}
