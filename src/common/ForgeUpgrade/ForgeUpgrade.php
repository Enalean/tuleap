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
use PDO;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function count;
use const PHP_EOL;

/**
 * Centralize upgrade of the Forge
 */
class ForgeUpgrade implements ForgeUpgradeRecordOnly
{
    private ForgeUpgradeDb $db;

    private \Tuleap\ForgeUpgrade\Bucket\BucketDb $bucketApi;

    private LoggerInterface $logger;

    private bool $force = false;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->db        = new ForgeUpgradeDb($pdo);
        $this->bucketApi = new \Tuleap\ForgeUpgrade\Bucket\BucketDb($pdo, $logger);
        $this->logger    = $logger;
    }

    public function setForce(bool $force): void
    {
        $this->force = $force;
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

        $buckets = $this->getBucketsToProceed($this->getBucketPaths());
        if (count($buckets) > 0) {
            switch ($func) {
                case 'update':
                    $this->doUpdate($buckets);
                    break;

                case 'check-update':
                    $this->doCheckUpdate($buckets);
                    break;
            }
        } else {
            $this->logger->info('System up-to-date');
        }
    }

    public function runUpdate(): void
    {
        $buckets = $this->getBucketsToProceed($this->getBucketPaths());
        if (count($buckets) > 0) {
            $this->doUpdate($buckets);
        } else {
            $this->logger->info('System up-to-date');
        }
    }

    public function isSystemUpToDate(): bool
    {
        return count($this->getBucketsToProceed($this->getBucketPaths())) === 0;
    }

    private function displayColoriedStatus(array $info): string
    {
        $status = ForgeUpgradeDb::statusLabel($info['status']);
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
    private function displayAlreadyAppliedForAllBuckets(): void
    {
        echo 'start date' . "           " . 'Execution' . "  " . 'Status' . "  " . 'Id' . "  " . 'Script' . PHP_EOL;
        foreach ($this->db->getAllBuckets() as $row) {
            echo $this->displayColoriedStatus($row);
        }
    }

    /**
     * Displays all buckets' logs according to the option "bucket" is filled or not
     */
    private function doAlreadyApplied(): void
    {
        $this->displayAlreadyAppliedForAllBuckets();
    }

    public function recordOnlyCore(): void
    {
        $this->doRecordOnly(
            $this->getBucketsToProceed(
                [$this->getCoreBucketPath()]
            )
        );
    }

    public function recordOnlyPlugin(string $plugin_path): void
    {
        if (! is_dir($plugin_path)) {
            throw new \RuntimeException("$plugin_path is not a directory, cannot record-only buckets");
        }
        $this->doRecordOnly(
            $this->getBucketsToProceed(
                [$plugin_path]
            )
        );
    }

    /**
     * @psalm-param array<string, Bucket> $buckets
     */
    private function doRecordOnly(array $buckets): void
    {
        foreach ($buckets as $bucket) {
            $this->logger->info("[doRecordOnly] " . $bucket::class);
            $this->db->logStart($bucket);
            $this->db->logEnd($bucket, ForgeUpgradeDb::STATUS_SKIP);
        }
    }

    private function doUpdate(array $buckets): void
    {
        if ($this->runPreUp($buckets)) {
            $this->runUp($buckets);
        }
        $remaining_buckets = $this->getBucketsToProceed($this->getBucketPaths());
        if (count($remaining_buckets) !== 0) {
            $this->doUpdate($remaining_buckets);
        }
    }

    private function doCheckUpdate(array $buckets): void
    {
        foreach ($buckets as $bucket) {
            echo $bucket->getPath() . PHP_EOL;
            $lines = explode("\n", $bucket->description());
            foreach ($lines as $line) {
                echo "\t$line\n";
            }
        }
        $nb_buckets = count($buckets);
        echo $nb_buckets . " migrations pending\n";
        if ($nb_buckets > 0) {
            exit(1);
        }
    }

    /**
     * Run all preUp methods
     *
     * Run all possible preUp, if a dependency is defined between 2 scripts,
     * preUp of the script that depends on another is skipped.
     *
     * @todo: Add info on the number of buckets Success, Faild, Skipped
     */
    private function runPreUp(array $buckets): bool
    {
        $this->logger->info("Process all pre up checks");
        $result = true;
        foreach ($buckets as $bucket) {
            $className = $bucket::class;
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
    public function runUpBucket(Bucket $bucket, LoggerInterface $log): void
    {
        $this->db->logStart($bucket);

        $log->info("Processing " . $bucket::class);

        $bucket->preUp();
        $log->info("PreUp OK");

        $bucket->up();
        $log->info("Up OK");

        $bucket->postUp();
        $log->info("PostUp OK");

        $this->db->logEnd($bucket, ForgeUpgradeDb::STATUS_SUCCESS);
    }

    /**
     * Load all migrations and execute them
     *
     * If force option is set, all buckets will be run even if it fails
     * Else if not, buckets' execution will drop since one bucket fails
     */
    private function runUp(array $buckets): void
    {
        $this->logger->info('Start running migrations...');
        $has_encountered_failure = false;

        if (! $this->force) {
            try {
                foreach ($buckets as $bucket) {
                    $this->runUpBucket($bucket, $this->logger);
                    unset($bucket);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                if (isset($bucket)) {
                    $this->db->logEnd($bucket, ForgeUpgradeDb::STATUS_FAILURE);
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
                    $this->db->logEnd($bucket, ForgeUpgradeDb::STATUS_FAILURE);
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
     *
     * @psalm-return array<string, Bucket>
     */
    private function getBucketsToProceed(array $paths): array
    {
        $buckets = $this->getAllBuckets($paths);
        $sth     = $this->db->getAllBuckets([ForgeUpgradeDb::STATUS_SUCCESS, ForgeUpgradeDb::STATUS_SKIP]);
        foreach ($sth as $row) {
            $key = basename($row['script']);
            if (isset($buckets[$key])) {
                $this->logger->debug("Remove (already applied): $key");
                unset($buckets[$key]);
            }
        }
        return $buckets;
    }

    /**
     * Find all migration files and sort them in time order
     *
     * @param string[] $paths
     *
     * @return array<string, Bucket>
     */
    private function getAllBuckets(array $paths): array
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
     * @param array<string, Bucket> $buckets
     */
    private function findAllBucketsInPath(string $path, array &$buckets): void
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
    private function getBucketFinderIterator(string $dirPath): BucketFilter
    {
        $iter = new RecursiveDirectoryIterator($dirPath);
        $iter = new RecursiveIteratorIterator($iter, RecursiveIteratorIterator::SELF_FIRST);
        return new BucketFilter($iter);
    }

    /**
     * Append a bucket in the bucket candidate list
     *
     * @param array<string, Bucket> $buckets
     */
    private function queueMigrationBucket(SplFileInfo $file, array &$buckets): void
    {
        if ($file->isFile()) {
            $object = $this->getBucketClass($file);
            if ($object instanceof Bucket) {
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
    private function getBucketClass(SplFileInfo $scriptPath): ?Bucket
    {
        $bucket = null;
        $class  = $this->getClassName($scriptPath->getPathname());
        if (! class_exists($class)) {
            include_once $scriptPath->getPathname();
        }
        if (is_subclass_of($class, Bucket::class)) {
            $bucket = new $class($this->logger, $this->bucketApi);
            assert($bucket instanceof Bucket);
            $bucket->setPath($scriptPath->getPathname());
        }
        return $bucket;
    }

    /**
     * Deduce the class name from the script name
     *
     * migrations/201004081445_add_tables_for_docman_watermarking.php -> b201004081445_add_tables_for_docman_watermarking
     */
    private function getClassName(string $scriptPath): string
    {
        return 'b' . basename($scriptPath, '.php');
    }

    private function getBucketPaths(): array
    {
        $paths = [
            $this->getCoreBucketPath(),
        ];
        foreach ($this->db->getActivePlugins() as $rows) {
            $plugin_db_path = dirname(__DIR__, 3) . '/plugins/' . $rows['name'] . '/db';
            if (is_dir($plugin_db_path)) {
                $paths[] = $plugin_db_path;
            }
        }
        return $paths;
    }

    private function getCoreBucketPath(): string
    {
        return dirname(__DIR__, 2) . '/db/mysql/updates';
    }
}
