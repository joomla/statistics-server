# Joomla Environment Stats

In order to better understand our install base and end user environments, a plugin has been created to send those stats back to a Joomla
controlled central server. No worries though, __no__ identifying data is captured at any point, and we only keep the data last sent to us.

## Build Status
Travis-CI: [![Build Status](https://travis-ci.org/joomla/statistics-server.png)](https://travis-ci.org/joomla/statistics-server)
Scrutinizer-CI: [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/joomla/statistics-server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/joomla/statistics-server/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/joomla/statistics-server/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/joomla/statistics-server/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/joomla/statistics-server/badges/build.png?b=master)](https://scrutinizer-ci.com/g/joomla/statistics-server/build-status/master)

## Requirements

* PHP 7.2+
* PDO with MySQL support
* MySQL
* Composer
* Apache with mod_rewrite enabled and configured to allow the .htaccess file to be read

## Installation

1. Clone this repo on your web server
2. Create a database on your MySQL server
3. Copy `etc/config.dist.json` to `etc/config.json` and fill in your database credentials
4. Run the `composer install` command to install all dependencies
5. Run the `bin/stats install` command to create the application's database
6. Run the `php cli/cron-update-versions.php` script to create the two files `versions/joomla.json` and `versions/php.json`
7. Setup a cronjob for `php cli/cron-update-versions.php` to keep the version JSONs up-to-date.

## Additional Configuration

The `DisplayStatisticsController` optionally supports additional configuration values which affect the application's behavior, to include:

* Raw Data Access - The API supports requesting the raw, unfiltered API data by sending a `Joomla-Raw` header with the API request. The value of this must match the `stats.rawdata` configuration key.

Additionally, the application behavior is affected by the following configuration settings:

* Error Reporting - The `errorReporting` configuration key can be set to a valid bitmask to be passed into the `error_reporting()` function
* Logging - The application's logging levels can be fine tuned by adjusting the `log` configuration keys:
    * `log.level` - The default logging level to use for all application loggers
    * `log.application` - The logging level to use specifically for the `monolog.handler.application` logger; defaults to the `log.level` value
    * `log.database` - The logging level to use specifically for the `monolog.handler.database` logger; defaults to the `log.level` value (Note: if `database.debug` is set to true then this level will ALWAYS correspond to the debug level)

## Deployments
* Joomla's Jenkins server will automatically push any commits to the `master` branch to the production server
    * TODO - Future iterations of this setup should require a passing Travis-CI build before deploying
* Because of the use of custom delimiters in the database schema (which are not parsed correctly with PDO), database migrations are not automatically executed
* If a change is pushed that includes updates to the database schema, then the merger needs to log into the server and run any migrations required; the application's `database:migrate` command will take care of this
    * `php /path/to/application/bin/stats database:migrate`
* Don’t put any triggers inside the migrations, those should be added to the main `etc/mysql.sql` schema file then manually run on the database using your preferred database management tool
