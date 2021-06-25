<?php
/**
 * Copyright (c) Enalean SAS, 2011-Present. All Rights Reserved.
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

/**
 * Centralize upgrade of the Forge
 */
class ForgeUpgrade
{
    /**
     * @var ForgeUpgrade_Db_Driver_Abstract
     */
    protected $dbDriver;

    /**
     * @var ForgeUpgradeDb
     */
    protected $db;

    /**
     * Contains all bucket API
     * @var Array
     */
    protected $bucketApi = [];

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var array
     */
    protected $buckets = null;

    /**
     * Constructor
     */
    public function __construct(ForgeUpgrade_Db_Driver_Abstract $dbDriver)
    {
        $this->dbDriver                            = $dbDriver;
        $this->db                                  = new ForgeUpgrade_Db($dbDriver->getPdo());
        $this->bucketApi['ForgeUpgrade_Bucket_Db'] = new ForgeUpgrade_Bucket_Db($dbDriver->getPdo());
    }

    /**
     * Set all options of forge upgrade
     *
     * If an option is not set, fill with default
     *
     * @param Array $options
     *
     * @return void
     */
    public function setOptions(array $options)
    {
        if (! isset($options['core']['path'])) {
            $options['core']['path'] = [];
        }
        if (! isset($options['core']['include_path'])) {
            $options['core']['include_path'] = [];
        }
        if (! isset($options['core']['exclude_path'])) {
            $options['core']['exclude_path'] = [];
        }
        if (! isset($options['core']['dbdriver'])) {
            $options['core']['dbdriver'] = null;
        }
        if (! isset($options['core']['ignore_preup'])) {
            $options['core']['ignore_preup'] = false;
        }
        if (! isset($options['core']['force'])) {
            $options['core']['force'] = false;
        }
        if (! isset($options['core']['bucket'])) {
            $options['core']['bucket'] = null;
        }
        $this->options = $options;
    }

    /**
     * Run all available migrations
     */
    public function run($func)
    {
        // Commands without path
        switch ($func) {
            case 'already-applied':
                $this->doAlreadyApplied();
                return;
        }

        // Commands that rely on path
        if (count($this->options['core']['path']) == 0) {
            $this->log()->error('No migration path');
            return false;
        }
        $buckets = $this->getBucketsToProceed($this->options['core']['path']);
        if (count($buckets) > 0) {
            switch ($func) {
                case 'record-only':
                    $this->doRecordOnly($buckets);
                    break;

                case 'update':
                    $this->doUpdate($buckets);
                    break;

                case 'check-update':
                    $this->doCheckUpdate($buckets);
                    break;

                case 'run-pre':
                    $this->runPreUp($buckets);
                    break;
            }
        } else {
            $this->log()->info('System up-to-date');
        }
    }


    /**
     * Displays detailed bucket's logs for a given bucket Id
     *
     * @param int $bucketId
     */
    protected function displayAlreadyAppliedPerBucket($bucketId)
    {
        echo '';
        $summary = $this->db->getBucketsSummarizedLogs($bucketId);
        if ($summary) {
            echo 'Start date' . "           " . 'Execution' . "  " . 'Status' . "  " . 'Id' . "  " . 'Script' . PHP_EOL;
            $logs = $summary->fetchAll();
            echo($this->displayColoriedStatus($logs[0]));
        }

        echo "Detailed logs execution for bucket " . $bucketId . PHP_EOL;
        $details = $this->db->getBucketsDetailedLogs($bucketId);
        if ($details) {
            echo 'Start date' . "           " . 'Level' . "  " . 'Message' . PHP_EOL;
            foreach ($details->fetchAll() as $row) {
                $level   = $row['level'];
                $message = $row['timestamp'] . "  " . $level . "  " . $row['message'] . PHP_EOL;
                echo LoggerAppenderConsoleColor::chooseColor($level, $message);
            }
        }
    }


    protected function displayColoriedStatus($info)
    {
        $status = $this->db->statusLabel($info['status']);
        switch ($status) {
            case 'error':
            case 'failure':
                $color = LoggerAppenderConsoleColor::RED;
                break;

            case 'success':
                $color = LoggerAppenderConsoleColor::GREEN;
                break;

            case 'skipped':
                $color = LoggerAppenderConsoleColor::YELLOW;
                break;

            default:
                break;
        }
        return $color . ($info['start_date'] . "  " . $info['execution_delay'] . "  " . ucfirst($status) . "  " . $info['id'] . "  " . $info['script'] . PHP_EOL . LoggerAppenderConsoleColor::NOCOLOR);
    }

    /**
     * Displays logs of all buckets already applied
     */
    protected function displayAlreadyAppliedForAllBuckets()
    {
        $color = '';
        echo 'start date' . "           " . 'Execution' . "  " . 'Status' . "  " . 'Id' . "  " . 'Script' . PHP_EOL;
        foreach ($this->db->getAllBuckets() as $row) {
            echo $this->displayColoriedStatus($row);
        }
    }


    /**
     * Displays detailed bucket's logs for a given bucket Id
     * Or all buckets' logs according to the option "bucket" is filled or not
     */
    protected function doAlreadyApplied()
    {
        if ($this->options['core']['bucket']) {
            $this->displayAlreadyAppliedPerBucket($this->options['core']['bucket']);
        } else {
            $this->displayAlreadyAppliedForAllBuckets();
        }
    }

    protected function doRecordOnly($buckets)
    {
        foreach ($buckets as $bucket) {
            $this->log()->info("[doRecordOnly] " . get_class($bucket));
            $this->db->logStart($bucket);
            $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_SKIP);
        }
    }

    protected function doUpdate($buckets)
    {
        if (! $this->options['core']['ignore_preup']) {
            if ($this->runPreUp($buckets)) {
                $this->runUp($buckets);
            }
        } else {
             $this->runUp($buckets);
        }
    }


    protected function doCheckUpdate($buckets)
    {
        foreach ($buckets as $bucket) {
            echo $bucket->getPath() . PHP_EOL;
            $lines = explode("\n", $bucket->description());
            foreach ($lines as $line) {
                echo "\t$line\n";
            }
        }
        echo count($buckets) . " migrations pending\n";
    }

    /**
     * Run all preUp methods
     *
     * Run all possible preUp, if a dependency is defined between 2 scripts,
     * preUp of the script that depends on another is skipped.
     *
     * @todo: Add info on the number of buckets Success, Faild, Skipped
     */
    public function runPreUp($buckets)
    {
        $this->log()->info("Process all pre up checks");
        $result = true;
        foreach ($buckets as $bucket) {
            $className = get_class($bucket);
            try {
                if (! $bucket->dependsOn()) {
                    $bucket->preUp();
                    $this->log()->info("OK: $className");
                } else {
                    $this->log()->info("SKIP: " . $className . " (depends on a migration not already applied)");
                }
            } catch (Exception $e) {
                $this->log()->error($className . ': ' . $e->getMessage());
                $result = false;
            }
        }
        if ($result) {
            $this->log()->info("PreUp checks OK");
        } else {
            $this->log()->error("PreUp checks FAILD");
        }

        return $result;
    }

    /**
     * It executes the bucket and logs its status
     *
     * @param ForgeUpgrade_Bucket  $bucket
     * @param Logger               $log
     */
    public function runUpBucket($bucket, $log)
    {
        $this->db->logStart($bucket);

        // Prepare a specific logger that will be used to store all
        // Bucket traces into the database so the buckets and it's logs
        // will be linked
        $bucketAppender = $this->dbDriver->getBucketLoggerAppender($bucket);
        $log->addAppender($bucketAppender);
        $bucket->setLoggerParent($log);

        $log->info("Processing " . get_class($bucket));

        if (! $this->options['core']['ignore_preup']) {
            $bucket->preUp();
            $log->info("PreUp OK");
        }

        $bucket->up();
        $log->info("Up OK");

        $bucket->postUp();
        $log->info("PostUp OK");

        $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_SUCCESS);
        $log->removeAppender($bucketAppender);
    }

    /**
     * Load all migrations and execute them
     *
     * If force option is set, all buckets will be run even if it fails
     * Else if not, buckets' execution will drop since one bucket fails
     * @param String $scriptPath Path to the script to execute
     *
     * @return void
     */
    protected function runUp($buckets)
    {
        $log = $this->log();
        $log->info('Start running migrations...');

        // Keep original logger: $log will be modified in runUpBucket in order
        // to store results in the database attached to the bucket.
        $origLogger = clone $log;
        if (! $this->options['core']['force']) {
            try {
                foreach ($buckets as $bucket) {
                    $this->runUpBucket($bucket, $log);
                    unset($bucket);
                }
            } catch (Exception $e) {
                $log->error($e->getMessage());
                $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_FAILURE);
            }
        } else {
            foreach ($buckets as $bucket) {
                try {
                    $this->runUpBucket($bucket, $log);
                    unset($bucket);
                } catch (Exception $e) {
                    $log->error($e->getMessage());
                    $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_FAILURE);
                }
            }
        }
        $log = $origLogger;
    }

    /**
     * Return all the buckets not already applied
     *
     * @param array $dirPath
     */
    protected function getBucketsToProceed(array $dirPath)
    {
        if (! isset($this->buckets)) {
            $this->buckets = $this->getAllBuckets($dirPath);
            $sth           = $this->db->getAllBuckets([ForgeUpgrade_Db::STATUS_SUCCESS, ForgeUpgrade_Db::STATUS_SKIP]);
            foreach ($sth as $row) {
                $key = basename($row['script']);
                if (isset($this->buckets[$key])) {
                    $this->log()->debug("Remove (already applied): $key");
                    unset($this->buckets[$key]);
                }
            }
        }
        return $this->buckets;
    }

    /**
     * Find all migration files and sort them in time order
     *
     * @return Array of SplFileInfo
     */
    protected function getAllBuckets(array $paths)
    {
        $buckets = [];
        foreach ($paths as $path) {
            $this->log()->debug("Look for buckets in $path");
            $this->findAllBucketsInPath($path, $buckets);
        }
        ksort($buckets, SORT_STRING);
        return $buckets;
    }

    /**
     * Fill $buckets array with all available buckets in $path
     *
     * @param String $path
     * @param Array $buckets
     */
    protected function findAllBucketsInPath($path, &$buckets)
    {
        if (is_dir($path)) {
            $iter = $this->getBucketFinderIterator($path);
            foreach ($iter as $file) {
                $this->queueMigrationBucket($file, $buckets);
            }
        } else {
            $this->queueMigrationBucket(new SplFileInfo($path), $buckets);
        }
    }

    /**
     * Build iterator to find buckets in a file hierarchy
     *
     * @param String $dirPath
     *
     * @return ForgeUpgrade_BucketFilter
     */
    protected function getBucketFinderIterator($dirPath)
    {
        $iter = new RecursiveDirectoryIterator($dirPath);
        $iter = new RecursiveIteratorIterator($iter, RecursiveIteratorIterator::SELF_FIRST);
        $iter = new ForgeUpgrade_BucketFilter($iter);
        $iter->setIncludePaths($this->options['core']['include_path']);
        $iter->setExcludePaths($this->options['core']['exclude_path']);
        return $iter;
    }

    /**
     * Append a bucket in the bucket candidate list
     *
     *
     * @return void
     */
    protected function queueMigrationBucket(SplFileInfo $file, &$buckets)
    {
        if ($file->isFile()) {
            $object = $this->getBucketClass($file);
            if ($object) {
                $this->log()->debug("Valid bucket: $file");
                $buckets[basename($file->getPathname())] = $object;
            } else {
                $this->log()->debug("Invalid bucket: $file");
            }
        }
    }

    /**
     * Create a new bucket object defined in given file
     *
     * @param SplFileInfo $scriptPath Path to the bucket definition
     *
     * @return ForgeUpgrade_Bucket
     */
    protected function getBucketClass(SplFileInfo $scriptPath)
    {
        $bucket = null;
        $class  = $this->getClassName($scriptPath->getPathname());
        if (! class_exists($class)) {
            include $scriptPath->getPathname();
        }
        if ($class != '' && class_exists($class)) {
            $bucket = new $class();
            $bucket->setPath($scriptPath->getPathname());
            $this->addBucketApis($bucket);
        }
        return $bucket;
    }

    /**
     * Add all available API to the given bucket
     *
     *
     * @return void
     */
    protected function addBucketApis(ForgeUpgrade_Bucket $bucket)
    {
        $bucket->setAllApi($this->bucketApi);
    }

    /**
     * Deduce the class name from the script name
     *
     * migrations/201004081445_add_tables_for_docman_watermarking.php -> b201004081445_add_tables_for_docman_watermarking
     *
     * @param String $scriptPath Path to the script to execute
     *
     * @return String
     */
    protected function getClassName($scriptPath)
    {
        return 'b' . basename($scriptPath, '.php');
    }

    /**
     * Wrapper for Logger
     *
     * @return Logger
     */
    protected function log()
    {
        if (! $this->log) {
            $this->log = Logger::getLogger(self::class);
        }
        return $this->log;
    }

    public function setLogger(Logger $log)
    {
        $this->log = $log;
    }
}
