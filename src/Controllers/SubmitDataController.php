<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Controllers;

use Joomla\Controller\AbstractController;
use Joomla\StatsServer\Decorators\ValidateVersion;
use Joomla\StatsServer\Repositories\InfluxdbRepository;
use Joomla\StatsServer\Repositories\StatisticsRepository;
use Laminas\Diactoros\Response\JsonResponse;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

/**
 * Controller for processing submitted statistics data.
 *
 * @method         \Joomla\Application\WebApplication  getApplication()  Get the application object.
 * @property-read  \Joomla\Application\WebApplication  $app              Application object
 */
class SubmitDataController extends AbstractController
{
    use ValidateVersion;

    /**
     * Statistics repository.
     *
     * @var  StatisticsRepository
     */
    private $repository;

    /**
     * Influxdb repository.
     *
     * @var  InfluxdbRepository
     */
    private $influxdbRepository;

    /**
     * Filesystem adapter for the snapshots space.
     *
     * @var  Filesystem
     */
    private $filesystem;

    /**
     * Allowed Database Types.
     *
     * @var  array
     */
    private $databaseTypes = [
        'mysql',
        'mysqli',
        'pgsql',
        'pdomysql',
        'postgresql',
        'sqlazure',
        'sqlsrv',
    ];

    /**
     * Constructor.
     *
     * @param   StatisticsRepository  $repository  Statistics repository.
     * @param   Filesystem            $filesystem  Filesystem adapter for the versions space.
     */
    public function __construct(StatisticsRepository $repository, Filesystem $filesystem, InfluxdbRepository $influxdbRepository = null)
    {
        $this->repository         = $repository;
        $this->filesystem         = $filesystem;
        $this->influxdbRepository = $influxdbRepository;
    }

    /**
     * Execute the controller.
     *
     * @return  boolean
     */
    public function execute()
    {
        $input = $this->getInput();

        $data = [
            'php_version' => $input->getRaw('php_version', ''),
            'db_version'  => $input->getRaw('db_version', ''),
            'cms_version' => $input->getRaw('cms_version', ''),
            'unique_id'   => $input->getString('unique_id'),
            'db_type'     => $input->getString('db_type', ''),
            'server_os'   => $input->getString('server_os'),
        ];

        // Backup the original POST before manipulating/validating data
        $originalData = $data;

        // Validate the submitted data
        $data['php_version'] = $this->checkPHPVersion($data['php_version']);
        $data['cms_version'] = $this->checkCMSVersion($data['cms_version']);
        $data['db_type']     = $this->checkDatabaseType($data['db_type']);
        $data['db_version']  = $this->validateVersionNumber($data['db_version']);

        // We require at a minimum a unique ID and the CMS version
        if (empty($data['unique_id']) || (empty($data['cms_version']) && $data['cms_version'] !== false)) {
            $this->getApplication()->getLogger()->info(
                'Missing required data from request.',
                ['postData' => $originalData]
            );

            /** @var JsonResponse $response */
            $response = $this->getApplication()->getResponse();
            $response = $response->withPayload(
                [
                    'error'   => true,
                    'message' => 'There was an error storing the data.',
                ]
            );
            $response = $response->withStatus(500);

            $this->getApplication()->setResponse($response);

            return true;
        }

        // If the below data does not pass tests, we do not accept the POST
        if ($data['php_version'] === false || $data['cms_version'] === false || $data['db_type'] === false || $data['db_version'] === false) {
            /** @var JsonResponse $response */
            $response = $this->getApplication()->getResponse();
            $response = $response->withPayload(
                [
                    'error'   => true,
                    'message' => 'Invalid data submission.',
                ]
            );
            $response = $response->withStatus(500);

            $this->getApplication()->setResponse($response);

            return true;
        }

        // Account for configuration differences with 4.0
        if (version_compare($data['cms_version'], '4.0', 'ge')) {
            // For 4.0 and later, we map `mysql` to the `pdomysql` option to correctly track the database type
            if ($data['db_type'] === 'mysql') {
                $data['db_type'] = 'pdomysql';
            }
        }

        $this->repository->save((object) $data);
        if ($this->influxdbRepository instanceof InfluxdbRepository) {
            $this->influxdbRepository->save((object) $data);
        }

        /** @var JsonResponse $response */
        $response = $this->getApplication()->getResponse();
        $response = $response->withPayload(
            [
                'error'   => false,
                'message' => 'Data saved successfully',
            ]
        );

        $this->getApplication()->setResponse($response);

        return true;
    }

    /**
     * Check the CMS version.
     *
     * @param   string  $version  The version number to check.
     *
     * @return  string|boolean  The version number on success or boolean false on failure.
     */
    private function checkCMSVersion(string $version)
    {
        $version = $this->validateVersionNumber($version);

        // If the version number is invalid, don't go any further
        if ($version === false) {
            return false;
        }

        // Joomla only uses major.minor.patch so everything else is invalid
        $explodedVersion = explode('.', $version);

        if (\count($explodedVersion) > 3) {
            return false;
        }

        try {
            $validVersions = json_decode($this->filesystem->read('joomla.json'), true);
        } catch (FileNotFoundException $exception) {
            throw new \RuntimeException('Missing Joomla! release listing', 500, $exception);
        }

        // Check that the version is in our valid release list
        if (!\in_array($version, $validVersions)) {
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
     */
    private function checkDatabaseType(string $database)
    {
        if (!\in_array($database, $this->databaseTypes)) {
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
     */
    private function checkPHPVersion(string $version)
    {
        $version = $this->validateVersionNumber($version);

        // If the version number is invalid, don't go any further
        if ($version === false) {
            return false;
        }

        // We only track versions based on major.minor.patch so everything else is invalid
        $explodedVersion = explode('.', $version);

        if (\count($explodedVersion) > 3) {
            return false;
        }

        try {
            $validVersions = json_decode($this->filesystem->read('php.json'), true);
        } catch (FileNotFoundException $exception) {
            throw new \RuntimeException('Missing PHP release listing', 500, $exception);
        }

        // Check that the version is in our valid release list
        if (!\in_array($version, $validVersions)) {
            return false;
        }

        return $version;
    }
}
