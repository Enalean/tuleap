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
$func    = 'help';
$options = [];
for ($i = 1; $i < $argc; $i++) {
    // Commands
    switch ($argv[$i]) {
        case 'help':
        case 'record-only':
        case 'update':
        case 'check-update':
        case 'run-pre':
        case 'already-applied':
            $func = $argv[$i];
            break;
    }

    // Options

    // --config
    if (preg_match('/--config=(.*)/', $argv[$i], $matches)) {
        if (is_file($matches[1])) {
            $options = parse_ini_file($matches[1], true);
        }
    }

    // --path
    if (preg_match('/--path=(.*)/', $argv[$i], $matches)) {
        if (is_dir($matches[1]) || is_file($matches[1])) {
            $options['core']['path'][] = $matches[1];
        } else {
            echo 'Error "' . $matches[1] . '" is not a valid directory' . PHP_EOL;
        }
    }

    // --include
    if (preg_match('/--include=(.*)/', $argv[$i], $matches)) {
        $options['core']['include_path'][] = surroundBy($matches[1], '/');
    }

    // --exclude
    if (preg_match('/--exclude=(.*)/', $argv[$i], $matches)) {
        $options['core']['exclude_path'][] = surroundBy($matches[1], '/');
    }

    // --driver
    if (preg_match('/--dbdriver=(.*)/', $argv[$i], $matches)) {
        echo "Error --dbdriver option is no longer supported" . PHP_EOL;
        exit(1);
    }

    //--ignore-preup
    if (preg_match('/--ignore-preup/', $argv[$i], $matches)) {
        $options['core']['ignore_preup'] = true;
    }

    //--force
    if (preg_match('/--force/', $argv[$i], $matches)) {
        $options['core']['force'] = true;
    }

    // --level
    if (preg_match('/--verbose=(.*)/', $argv[$i], $matches)) {
        if (in_array($matches[1], ['ALL', 'WARN', 'FATAL', 'OFF'])) {
            echo "Error: `" . $matches[1] . "` level is no longer supported see usage with --help" . PHP_EOL;
            exit(1);
        }
        $options['core']['verbose'] = Logger::toMonologLevel($matches[1]);
    }

    // --bucket
    if (preg_match('/--bucket=(.*)/', $argv[$i], $matches)) {
        $options['core']['bucket'] = $matches[1];
    }
}

if ($func == 'help') {
    usage();
    exit;
}

if (! isset($options['core']['verbose'])) {
    $options['core']['verbose'] = Logger::INFO;
}

$logger = new Logger('forgeupgrade');
$logger->pushHandler(new StreamHandler(STDOUT, $options['core']['verbose']));

// Go
\ForgeConfig::loadLocalInc();
\ForgeConfig::loadDatabaseInc();
$upg = new ForgeUpgrade(
    DBFactory::getMainTuleapDBConnection()->getDB()->getPdo(),
    $logger
);
$upg->setOptions($options);
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
run-pre          Run pending migration buckets "pre" checks
update           Execute pending migration buckets
record-only      Record all available buckets as executed in the database without
                 actually executing them

Options:
  --config=[/path]         Path to ForgeUpgrade config file (you can define all options in a config.ini file)
  --path=[/path]           Path where to find migration buckets [default: current dir]
  --include=[/path]        Only consider paths that contains given pattern
  --exclude=[/path]        Don't consider paths that contains given pattern

  --ignore-preup           Execute migration buckets whithout running "pre" checks
  --force                  Execute migration buckets even there are errors
  --verbose=[level]        How verbose: DEBUG, INFO, WARNING, ERROR
                           Default: INFO
  --bucket=[bucket id]     Used with already-applied command, to display the detailed
                           log for this  bucket

EOT;
}

/**
 * Surround a string by a char if not present
 *
 * @param String $str  String to surround
 * @param String $char Char to add
 *
 * @return String
 */
function surroundBy($str, $char)
{
    if (strpos($str, $char) === false) {
        $str = $char . $str . $char;
    } else {
        if (strpos($str, $char) !== 0) {
            $str = $char . $str;
        }
        if (strrpos($str, $char) !== (strlen($str) - 1)) {
            $str = $str . $char;
        }
    }
    return $str;
}
