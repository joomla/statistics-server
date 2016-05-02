<?php
namespace Stats\Tests\Views\Stats;

use Stats\Models\StatsModel;
use Stats\Views\Stats\StatsJsonView;

/**
 * Test class for \Stats\Views\Stats\StatsJsonView
 */
class StatsJsonViewTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @testdox The authorized raw flag is set to the view
	 *
	 * @covers  Stats\Views\Stats\StatsJsonView::isAuthorizedRaw
	 */
	public function testTheAuthorizedRawFlagIsSetToTheView()
	{
		$mockModel = $this->getMockBuilder(StatsModel::class)
			->disableOriginalConstructor()
			->getMock();

		$authorizedRaw = true;

		$view = new StatsJsonView($mockModel);
		$view->isAuthorizedRaw($authorizedRaw);

		$this->assertAttributeSame($authorizedRaw, 'authorizedRaw', $view);
	}

	/**
	 * @testdox The statistics data is returned
	 *
	 * @covers  Stats\Views\Stats\StatsJsonView::buildResponseData
	 * @covers  Stats\Views\Stats\StatsJsonView::render
	 * @covers  Stats\Views\Stats\StatsJsonView::sanitizeData
	 */
	public function testTheStatisticsDataIsReturned()
	{
		$mockModel = $this->getMockBuilder(StatsModel::class)
			->disableOriginalConstructor()
			->getMock();

		$mockModel->expects($this->once())
			->method('getItems')
			->willReturn(
				[
					[
						[
							'unique_id'   => '1a',
							'php_version' => PHP_VERSION,
							'cms_version' => '3.5.0',
							'db_type'     => 'mysql',
							'db_version'  => '5.6.25',
							'server_os'   => 'Darwin 14.1.0'
						],
						[
							'unique_id'   => '2b',
							'php_version' => PHP_VERSION,
							'cms_version' => '3.5.0',
							'db_type'     => 'postgresql',
							'db_version'  => '9.4.0',
							'server_os'   => 'Darwin 14.1.0'
						],
						[
							'unique_id'   => '3c',
							'php_version' => PHP_VERSION,
							'cms_version' => '3.5.0',
							'db_type'     => 'sqlsrv',
							'db_version'  => '10.50.2500',
							'server_os'   => ''
						],
					]
				]
			);

		$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		$returnData = [
			'data' => [
				'php_version' => [$phpVersion => 100],
				'db_type'     => ['mysql' => round((1 / 3) * 100, 2), 'postgresql' => round((1 / 3) * 100, 2), 'sqlsrv' => round((1 / 3) * 100, 2)],
				'db_version'  => ['5.6' => round((1 / 3) * 100, 2), '9.4' => round((1 / 3) * 100, 2), '10.50' => round((1 / 3) * 100, 2)],
				'cms_version' => ['3.5.0' => 100],
				'server_os'   => ['Darwin' => round((2 / 3) * 100, 2), 'unknown' => round((1 / 3) * 100, 2)],
				'total'       => 3
			]
		];

		$view = new StatsJsonView($mockModel);

		$this->assertSame($returnData, json_decode($view->render(), true));
	}

	/**
	 * @testdox The raw statistics data is returned
	 *
	 * @covers  Stats\Views\Stats\StatsJsonView::buildResponseData
	 * @covers  Stats\Views\Stats\StatsJsonView::render
	 */
	public function testTheRawStatisticsDataIsReturned()
	{
		$mockModel = $this->getMockBuilder(StatsModel::class)
			->disableOriginalConstructor()
			->getMock();

		$mockModel->expects($this->once())
			->method('getItems')
			->willReturn(
				[
					[
						[
							'unique_id'   => '1a',
							'php_version' => PHP_VERSION,
							'cms_version' => '3.5.0',
							'db_type'     => 'mysql',
							'db_version'  => '5.6.25',
							'server_os'   => 'Darwin 14.1.0'
						],
						[
							'unique_id'   => '2b',
							'php_version' => PHP_VERSION,
							'cms_version' => '3.5.0',
							'db_type'     => 'postgresql',
							'db_version'  => '9.4.0',
							'server_os'   => 'Darwin 14.1.0'
						],
						[
							'unique_id'   => '3c',
							'php_version' => PHP_VERSION,
							'cms_version' => '3.5.0',
							'db_type'     => 'sqlsrv',
							'db_version'  => '10.50.2500',
							'server_os'   => ''
						],
					]
				]
			);

		$returnData = [
			'data' => [
				'php_version' => [
					[
						'name'  => PHP_VERSION,
						'count' => 3
					]
				],
				'db_type'     => [
					[
						'name'  => 'mysql',
						'count' => 1
					],
					[
						'name'  => 'postgresql',
						'count' => 1
					],
					[
						'name'  => 'sqlsrv',
						'count' => 1
					],
				],
				'db_version'  => [
					[
						'name'  => '5.6.25',
						'count' => 1
					],
					[
						'name'  => '9.4.0',
						'count' => 1
					],
					[
						'name'  => '10.50.2500',
						'count' => 1
					],
				],
				'cms_version' => [
					[
						'name'  => '3.5.0',
						'count' => 3
					],
				],
				'server_os'   => [
					[
						'name'  => 'Darwin 14.1.0',
						'count' => 2
					],
					[
						'name'  => 'unknown',
						'count' => 1
					],
				],
				'total'       => 3
			]
		];

		$view = new StatsJsonView($mockModel);
		$view->isAuthorizedRaw(true);

		$this->assertSame($returnData, json_decode($view->render(), true));
	}

	/**
	 * @testdox The statistics data for a single source is returned
	 *
	 * @covers  Stats\Views\Stats\StatsJsonView::buildResponseData
	 * @covers  Stats\Views\Stats\StatsJsonView::processSingleSource
	 * @covers  Stats\Views\Stats\StatsJsonView::render
	 * @covers  Stats\Views\Stats\StatsJsonView::sanitizeData
	 * @uses    Stats\Views\Stats\StatsJsonView::setSource
	 */
	public function testTheStatisticsDataForASingleSourceIsReturned()
	{
		$mockModel = $this->getMockBuilder(StatsModel::class)
			->disableOriginalConstructor()
			->getMock();

		$mockModel->expects($this->once())
			->method('getItems')
			->willReturn(
				[
					[
						'php_version' => PHP_VERSION, 'count' => 3
					],
				]
			);

		$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		$returnData = [
			'data' => [
				'php_version' => [$phpVersion => 100],
				'total'       => 3
			]
		];

		$view = new StatsJsonView($mockModel);
		$view->setSource('php_version');

		$this->assertSame($returnData, json_decode($view->render(), true));
	}

	/**
	 * @testdox The statistics data for the server OS source is returned
	 *
	 * @covers  Stats\Views\Stats\StatsJsonView::buildResponseData
	 * @covers  Stats\Views\Stats\StatsJsonView::processSingleSource
	 * @covers  Stats\Views\Stats\StatsJsonView::render
	 * @covers  Stats\Views\Stats\StatsJsonView::sanitizeData
	 * @uses    Stats\Views\Stats\StatsJsonView::setSource
	 */
	public function testTheStatisticsDataForTheServerOsSourceIsReturned()
	{
		$mockModel = $this->getMockBuilder(StatsModel::class)
			->disableOriginalConstructor()
			->getMock();

		$mockModel->expects($this->once())
			->method('getItems')
			->willReturn(
				[
					[
						'count' => 2, 'server_os' => 'Darwin 14.1.0',
					],
					[
						'count' => 1, 'server_os' => '',
					],
				]
			);

		$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		$returnData = [
			'data' => [
				'server_os' => ['Darwin' => round((2 / 3) * 100, 2), 'unknown' => round((1 / 3) * 100, 2)],
				'total'     => 3
			]
		];

		$view = new StatsJsonView($mockModel);
		$view->setSource('server_os');

		$this->assertSame($returnData, json_decode($view->render(), true));
	}

	/**
	 * @testdox The data source is set to the view
	 *
	 * @covers  Stats\Views\Stats\StatsJsonView::setSource
	 */
	public function testTheDataSourceIsSetToTheView()
	{
		$mockModel = $this->getMockBuilder(StatsModel::class)
			->disableOriginalConstructor()
			->getMock();

		$source = 'php_version';

		$view = new StatsJsonView($mockModel);
		$view->setSource('php_version');

		$this->assertAttributeSame($source, 'source', $view);
	}
}
