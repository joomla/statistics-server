<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2020 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

// Application constants
\define('APPROOT', \dirname(__DIR__));

// Ensure we've initialized Composer
if (!file_exists(APPROOT . '/vendor/autoload.php'))
{
	exit;
}

require APPROOT . '/vendor/autoload.php';


use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;

// Load the config
$configuration = new Registry;
$configuration->loadFile(APPROOT . '/etc/config.json');

$http = new HttpFactory;
$http = $http->getHttp([
    'userAgent' => 'Joomla! Statistics Server v1',
]);

$phpJsonData = [];
$joomlaJsonData = [];

// Get list of releases via https://downloads.joomla.org/api/v1/releases/cms/
$joomlaReleases = json_decode($http->get('https://downloads.joomla.org/api/v1/releases/cms/', ['Accept' => 'application/json'])->body);

if (!isset($joomlaReleases->releases))
{
    // Exiting here as it seems the API has issues.
    exit;
}

foreach ($joomlaReleases->releases as $joomlaRelease)
{
    /**
     * object(stdClass)#8 (4) {
     *   ["version"]=>
     *   string(6) "3.9.20"
     *   ["branch"]=>
     *   string(9) "Joomla! 3"
     *   ["date"]=>
     *   string(25) "2020-07-14T15:00:00+00:00"
     *   ["relationships"]=>
     *   object(stdClass)#5 (1) {
     *    ["signatures"]=>
     *    string(57) "https://downloads.joomla.org/api/v1/signatures/cms/3-9-20"
     *   }
     * }
     */

    // We only want Joomla 3 and 4 Releases here
    if ($joomlaRelease->branch === 'Joomla! 3' || $joomlaRelease->branch === 'Joomla! 4')
    {
        $joomlaJsonData[] = $joomlaRelease->version;
    }
}

if (!in_array('4.0.0', $joomlaJsonData))
{
    $joomlaJsonData[] = '4.0.0';
}

// Make sure the current release +1 is in that JSON
// Get the latest release via https://downloads.joomla.org/api/v1/latest/cms
$joomlaLatestReleases = json_decode($http->get('https://downloads.joomla.org/api/v1/latest/cms', ['Accept' => 'application/json'])->body);

if (!isset($joomlaLatestReleases->branches))
{
    // Exiting here as it seems the API has issues.
    exit;
}

foreach ($joomlaLatestReleases->branches as $joomlaLatestRelease)
{
    // We only want Joomla 3 and 4 Releases here
    if ($joomlaLatestRelease->branch === 'Joomla! 3' || $joomlaLatestRelease->branch === 'Joomla! 4')
    {
        list($major, $minor, $patch) = explode('.', $joomlaLatestRelease->version);
        $major = (int) $major;
        $minor = (int) $minor;
        $patch = (int) $patch;
        $patch++;

        $joomlaJsonData[] = $major . '.' . $minor . '.' . $patch;
    }
}

$php530found = false;

while ($php530found === false)
{
    $page = 0;

    // Get all PHP Releases via GitHub Releases
    $phpReleases = json_decode(
        $http->get(
            'https://api.github.com/repos/php/php-src/tags?page=' . $page,
            [
                'Accept' => 'application/vnd.github.v3+json',
                'token' => $configuration->get('github.gh.token')
            ]
        )->body
    );

    foreach ($phpReleases as $phpRelease)
    {

        var_dump($phpRelease);
        exit;

        if (substr($phpRelease->name, 0, 4) === 'php-')
        {
            //$phpVersion = preg_replace('/[^0-9.]/', '', $phpRelease->name);
            $phpVersion = str_replace('php-', '', $phpRelease->name);

            if (!in_array($phpVersion, $phpJsonData))
            {
                $phpJsonData[] = $phpVersion;
            }

            if ($phpVersion === '5.3.0')
            {
                $php530found = true;
            }
        }
    }

    $page++;
}



// Create output JSON
$joomlaJson = json_encode($joomlaJsonData);
$phpJson = json_encode($phpJsonData);


// Write JSON to file
file_put_contents(APPROOT . '/versions/joomla.json', $joomlaJson);
file_put_contents(APPROOT . '/versions/php.json', $phpJson);
