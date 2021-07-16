<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\DB\DBFactory;
use Tuleap\ForgeUpgrade\ForgeUpgrade;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

// An upgrade process shouldn't end because it takes too much time ot too
// memory.
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

// Parameters
$func      = 'help';
$verbosity = Logger::INFO;
$force     = false;
for ($i = 1; $i < $argc; $i++) {
    // Commands
    switch ($argv[$i]) {
        case 'help':
        case 'update':
        case 'check-update':
        case 'already-applied':
            $func = $argv[$i];
            break;
    }

    // Options

    // --config
    if (preg_match('/--config=(.*)/', $argv[$i], $matches)) {
        fwrite(STDERR, "Warning, --config is obsolete, you should no longer use it " . PHP_EOL);
    }

    // --path
    if (preg_match('/--path=(.*)/', $argv[$i], $matches)) {
        echo "Error --path option is no longer supported" . PHP_EOL;
        exit(1);
    }

    // --include
    if (preg_match('/--include=(.*)/', $argv[$i], $matches)) {
        echo "Error --include option is no longer supported" . PHP_EOL;
        exit(1);
    }

    // --exclude
    if (preg_match('/--exclude=(.*)/', $argv[$i], $matches)) {
        echo "Error --exclude option is no longer supported" . PHP_EOL;
        exit(1);
    }

    // --driver
    if (preg_match('/--dbdriver=(.*)/', $argv[$i], $matches)) {
        echo "Error --dbdriver option is no longer supported" . PHP_EOL;
        exit(1);
    }

    //--ignore-preup
    if (preg_match('/--ignore-preup/', $argv[$i], $matches)) {
        echo "Error --ignore-preup option is no longer supported" . PHP_EOL;
        exit(1);
    }

    //--force
    if (preg_match('/--force/', $argv[$i], $matches)) {
        $force = true;
    }

    // --level
    if (preg_match('/--verbose=(.*)/', $argv[$i], $matches)) {
        if (in_array($matches[1], ['ALL', 'WARN', 'FATAL', 'OFF'])) {
            echo "Error: `" . $matches[1] . "` level is no longer supported see usage with --help" . PHP_EOL;
            exit(1);
        }
        $verbosity = Logger::toMonologLevel($matches[1]);
    }

    // --bucket
    if (preg_match('/--bucket=(.*)/', $argv[$i], $matches)) {
        echo "Error --bucket option is no longer supported" . PHP_EOL;
        exit(1);
    }
}

if ($func == 'help') {
    usage();
    exit;
}

$logger = new Logger('forgeupgrade');
$logger->pushHandler(new StreamHandler(STDOUT, $verbosity));

// Go
\ForgeConfig::loadLocalInc();
\ForgeConfig::loadDatabaseInc();
$upg = new ForgeUpgrade(
    DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(),
    $logger
);
$upg->setForce($force);
$upg->run($func);

// Function definitions
/**
 * Print Help
 */
function usage()
{
    echo <<<EOT
Usage: forgeupgrade.php [options] command

Commands:
already-applied  List all applied buckets
check-update     List all available migration buckets not already applied (pending)
update           Execute pending migration buckets

Options:
  --force                  Execute migration buckets even there are errors
  --verbose=[level]        How verbose: DEBUG, INFO, WARNING, ERROR
                           Default: INFO
EOT;
}
