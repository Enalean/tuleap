<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\ForgeUpgrade;

use Exception;
use ForgeUpgrade_Bucket;
use ForgeUpgrade_Bucket_Db;
use ForgeUpgrade_BucketFilter;
use ForgeUpgrade_Db;
use ForgeUpgrade_Db_Driver_Abstract;
use LoggerAppenderConsoleColor;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function count;
use const PHP_EOL;

/**
 * Centralize upgrade of the Forge
 */
class ForgeUpgrade
{
    private ForgeUpgrade_Db_Driver_Abstract $dbDriver;

    private ForgeUpgrade_Db $db;

    private array $bucketApi = [];

    private LoggerInterface $logger;

    private ?array $buckets = null;

    private array $options;

    public function __construct(ForgeUpgrade_Db_Driver_Abstract $dbDriver, LoggerInterface $logger)
    {
        $this->dbDriver                            = $dbDriver;
        $this->db                                  = new ForgeUpgrade_Db($dbDriver->getPdo());
        $this->bucketApi['ForgeUpgrade_Bucket_Db'] = new ForgeUpgrade_Bucket_Db($dbDriver->getPdo(), $logger);
        $this->logger                              = $logger;
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
    public function run(string $func): void
    {
        // Commands without path
        switch ($func) {
            case 'already-applied':
                $this->doAlreadyApplied();
                return;
        }

        // Commands that rely on path
        if (count($this->options['core']['path']) == 0) {
            $this->logger->error('No migration path');
            return;
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
            $this->logger->info('System up-to-date');
        }
    }

    protected function displayColoriedStatus(array $info): string
    {
        $status = ForgeUpgrade_Db::statusLabel($info['status']);
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
                $color = LoggerAppenderConsoleColor::NOCOLOR;
                break;
        }
        return $color . ($info['start_date'] . "  " . $info['execution_delay'] . "  " . ucfirst(
            $status
        ) . "  " . $info['id'] . "  " . $info['script'] . PHP_EOL . LoggerAppenderConsoleColor::NOCOLOR);
    }

    /**
     * Displays logs of all buckets already applied
     */
    protected function displayAlreadyAppliedForAllBuckets(): void
    {
        echo 'start date' . "           " . 'Execution' . "  " . 'Status' . "  " . 'Id' . "  " . 'Script' . PHP_EOL;
        foreach ($this->db->getAllBuckets() as $row) {
            echo $this->displayColoriedStatus($row);
        }
    }

    /**
     * Displays all buckets' logs according to the option "bucket" is filled or not
     */
    protected function doAlreadyApplied(): void
    {
        $this->displayAlreadyAppliedForAllBuckets();
    }

    protected function doRecordOnly(array $buckets): void
    {
        foreach ($buckets as $bucket) {
            $this->logger->info("[doRecordOnly] " . get_class($bucket));
            $this->db->logStart($bucket);
            $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_SKIP);
        }
    }

    protected function doUpdate(array $buckets): void
    {
        if (! $this->options['core']['ignore_preup']) {
            if ($this->runPreUp($buckets)) {
                $this->runUp($buckets);
            }
        } else {
            $this->runUp($buckets);
        }
    }

    protected function doCheckUpdate(array $buckets): void
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
    public function runPreUp(array $buckets): bool
    {
        $this->logger->info("Process all pre up checks");
        $result = true;
        foreach ($buckets as $bucket) {
            $className = get_class($bucket);
            try {
                if (! $bucket->dependsOn()) {
                    $bucket->preUp();
                    $this->logger->info("OK: $className");
                } else {
                    $this->logger->info("SKIP: " . $className . " (depends on a migration not already applied)");
                }
            } catch (Exception $e) {
                $this->logger->error($className . ': ' . $e->getMessage());
                $result = false;
            }
        }
        if ($result) {
            $this->logger->info("PreUp checks OK");
        } else {
            $this->logger->error("PreUp checks FAILD");
            exit(1);
        }

        return $result;
    }

    /**
     * It executes the bucket and logs its status
     */
    public function runUpBucket(ForgeUpgrade_Bucket $bucket, LoggerInterface $log): void
    {
        $this->db->logStart($bucket);

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
    }

    /**
     * Load all migrations and execute them
     *
     * If force option is set, all buckets will be run even if it fails
     * Else if not, buckets' execution will drop since one bucket fails
     */
    protected function runUp(array $buckets): void
    {
        $this->logger->info('Start running migrations...');
        $has_encountered_failure = false;

        if (! $this->options['core']['force']) {
            try {
                foreach ($buckets as $bucket) {
                    $this->runUpBucket($bucket, $this->logger);
                    unset($bucket);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                if (isset($bucket)) {
                    $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_FAILURE);
                }
                $has_encountered_failure = true;
            }
        } else {
            foreach ($buckets as $bucket) {
                try {
                    $this->runUpBucket($bucket, $this->logger);
                    unset($bucket);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    $this->db->logEnd($bucket, ForgeUpgrade_Db::STATUS_FAILURE);
                    $has_encountered_failure = true;
                }
            }
        }

        if ($has_encountered_failure) {
            exit(1);
        }
    }

    /**
     * Return all the buckets not already applied
     */
    protected function getBucketsToProceed(array $dirPath): array
    {
        if ($this->buckets === null) {
            $this->buckets = $this->getAllBuckets($dirPath);
            $sth           = $this->db->getAllBuckets([ForgeUpgrade_Db::STATUS_SUCCESS, ForgeUpgrade_Db::STATUS_SKIP]);
            foreach ($sth as $row) {
                $key = basename($row['script']);
                if (isset($this->buckets[$key])) {
                    $this->logger->debug("Remove (already applied): $key");
                    unset($this->buckets[$key]);
                }
            }
        }
        return $this->buckets;
    }

    /**
     * Find all migration files and sort them in time order
     *
     * @param string[] $paths
     *
     * @return array<string, ForgeUpgrade_Bucket>
     */
    protected function getAllBuckets(array $paths): array
    {
        $buckets = [];
        foreach ($paths as $path) {
            $this->logger->debug("Look for buckets in $path");
            $this->findAllBucketsInPath($path, $buckets);
        }
        ksort($buckets, SORT_STRING);
        return $buckets;
    }

    /**
     * Fill $buckets array with all available buckets in $path
     *
     * @param array<string, ForgeUpgrade_Bucket> $buckets
     */
    protected function findAllBucketsInPath(string $path, array &$buckets): void
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
     */
    protected function getBucketFinderIterator(string $dirPath): ForgeUpgrade_BucketFilter
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
     * @param array<string, ForgeUpgrade_Bucket> $buckets
     */
    protected function queueMigrationBucket(SplFileInfo $file, array &$buckets): void
    {
        if ($file->isFile()) {
            $object = $this->getBucketClass($file);
            if ($object instanceof ForgeUpgrade_Bucket) {
                $this->logger->debug("Valid bucket: $file");
                $buckets[basename($file->getPathname())] = $object;
            } else {
                $this->logger->debug("Invalid bucket: $file");
            }
        }
    }

    /**
     * Create a new bucket object defined in given file
     */
    protected function getBucketClass(SplFileInfo $scriptPath): ?ForgeUpgrade_Bucket
    {
        $bucket = null;
        $class  = $this->getClassName($scriptPath->getPathname());
        if (! class_exists($class)) {
            include $scriptPath->getPathname();
        }
        if (is_subclass_of($class, ForgeUpgrade_Bucket::class)) {
            $bucket = new $class($this->logger);
            $bucket->setPath($scriptPath->getPathname());
            $this->addBucketApis($bucket);
        }
        return $bucket;
    }

    /**
     * Add all available API to the given bucket
     */
    protected function addBucketApis(ForgeUpgrade_Bucket $bucket): void
    {
        $bucket->setAllApi($this->bucketApi);
    }

    /**
     * Deduce the class name from the script name
     *
     * migrations/201004081445_add_tables_for_docman_watermarking.php -> b201004081445_add_tables_for_docman_watermarking
     */
    protected function getClassName(string $scriptPath): string
    {
        return 'b' . basename($scriptPath, '.php');
    }
}
