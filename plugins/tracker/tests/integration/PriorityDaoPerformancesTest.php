<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tracker_Artifact_PriorityDao;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

/**
 * Also need to increase the memory limit to execute properly
 * @group ToFatToRun
 */
final class PriorityDaoPerformancesTest extends TestIntegrationTestCase
{
    private Tracker_Artifact_PriorityDao $dao;
    private \ParagonIE\EasyDB\EasyDB $db;

    public function setUp(): void
    {
        $this->dao = new Tracker_Artifact_PriorityDao();
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testBenchmark()
    {
        $csv = [
            'labels' => [''],
        ];
        foreach (
            [
                100,
                10000,
                100000,
                500000,
                1000000,
            ] as $n
        ) {
            $csv['labels'][] = $n;
            $this->addToCSV($csv, $this->benchmarkPutAtTheEnd($n));
            $this->addToCSV($csv, $this->benchmarkMoveBefore1rank($n));
            $this->addToCSV($csv, $this->benchmarkMoveBeforeMiddle($n));
            $this->addToCSV($csv, $this->benchmarkMoveBeforeAll($n));
            $this->addToCSV($csv, $this->benchmarkMove10Before1rank($n));
        }
        $out = fopen('/tuleap/stats-artifact-priority.csv', 'w');
        foreach ($csv as $line) {
            fputcsv($out, $line);
        }
        fclose($out);
    }

    private function addToCSV(array &$csv, array $data)
    {
        list($title, $avg) = $data;
        if (! isset($csv[$title])) {
            $csv[$title] = [$title];
        }
        $csv[$title][] = $avg;
    }

    private function benchmarkPutAtTheEnd($n)
    {
        $title = "Time taken for put at the end";
        echo "$title for $n artifacts\n";
        $k     = 10;
        $times = [];
        for ($i = 1; $i <= $k; $i++) {
            $this->progress($i, $k);
            $this->generateRandomArtifactPriorities($n);
            $start = microtime(true);
            $this->dao->putArtifactAtTheEndWithoutTransaction($n + $i);
            $end     = microtime(true);
            $times[] = $end - $start;
        }
        echo "\n";
        return [$title, $this->displayStats($times)];
    }

    private function benchmarkMove10Before1rank($n)
    {
        $title = "Time taken for move 10 before (1 rank)";
        echo "$title for $n artifacts\n";
        return [$title, $this->benchmarkMove10BeforeRank($n, $n - 10 - 2)];
    }

    private function benchmarkMoveBefore1rank($n)
    {
        $title = "Time taken for move before (1 rank)";
        echo "$title for $n artifacts\n";
        return [$title, $this->benchmarkMoveBeforeRank($n, $n - 2)];
    }

    private function benchmarkMoveBeforeMiddle($n)
    {
        $title = "Time taken for move before (middle)";
        echo "$title for $n artifacts\n";
        return [$title, $this->benchmarkMoveBeforeRank($n, floor($n / 2))];
    }

    private function benchmarkMoveBeforeAll($n)
    {
        $title = "Time taken for move before (all)";
        echo "$title for $n artifacts\n";
        return [$title, $this->benchmarkMoveBeforeRank($n, 1)];
    }

    private function benchmarkMoveBeforeRank($n, $new_rank)
    {
        $k     = 10;
        $times = [];
        for ($i = 1; $i <= $k; $i++) {
            $this->progress($i, $k);
            $this->generateRandomArtifactPriorities($n);
            $row          = $this->dao->retrieve("SELECT artifact_id FROM tracker_artifact_priority_rank WHERE `rank` = $n - 1")->getRow();
            $artifact_id  = $row['artifact_id'];
            $row          = $this->dao->retrieve("SELECT artifact_id FROM tracker_artifact_priority_rank WHERE `rank` = $new_rank")->getRow();
            $successor_id = $row['artifact_id'];
            $start        = microtime(true);
            $this->dao->moveListOfArtifactsBefore([$artifact_id], $successor_id);
            $end     = microtime(true);
            $times[] = $end - $start;
        }
        echo "\n";
        return $this->displayStats($times);
    }

    private function benchmarkMove10BeforeRank($n, $new_rank)
    {
        $k     = 10;
        $times = [];
        for ($i = 1; $i <= $k; $i++) {
            $this->progress($i, $k);
            $this->generateRandomArtifactPriorities($n);
            $artifact_ids = [];
            foreach ($this->dao->retrieve("SELECT artifact_id FROM tracker_artifact_priority_rank WHERE `rank` >= $n - 10") as $row) {
                $artifact_ids[] = $row['artifact_id'];
            }
            $row          = $this->dao->retrieve("SELECT artifact_id FROM tracker_artifact_priority_rank WHERE `rank` = $new_rank")->getRow();
            $successor_id = $row['artifact_id'];
            $start        = microtime(true);
            $this->dao->moveListOfArtifactsBefore($artifact_ids, $successor_id);
            $end     = microtime(true);
            $times[] = $end - $start;
        }
        echo "\n";
        return $this->displayStats($times);
    }

    private function displayStats($times)
    {
        $avg = round(array_sum($times) * 1000 / count($times));
        echo "Min: " . round(min($times) * 1000) . "\n";
        echo "Max: " . round(max($times) * 1000) . "\n";
        echo "Avg: " . $avg . "\n";
        echo "\n";
        echo "\n";

        return $avg;
    }

    private function generateRandomArtifactPriorities($n)
    {
        $artifact_ids = range(1, $n);
        shuffle($artifact_ids);

        $inserts = [];
        for ($i = 0; $i < count($artifact_ids); $i++) {
            $inserts[] = "($artifact_ids[$i], $i)";
        }

        shuffle($inserts);
        foreach (array_chunk($inserts, 10000) as $chunk) {
            $sql  = "INSERT INTO tracker_artifact_priority_rank (artifact_id, `rank`) VALUES ";
            $sql .= implode(',', $chunk);
            $this->db->run($sql);
        }
        $this->db->run('ANALYZE TABLE tracker_artifact_priority_rank');
    }

    private function progress($done, $total)
    {
        $bar_size = (int) floor($done * 30 / $total);

        $status_bar  = "\r[";
        $status_bar .= str_repeat("=", $bar_size);
        if ($bar_size < 30) {
            $status_bar .= ">";
            $status_bar .= str_repeat(" ", 30 - $bar_size);
        } else {
            $status_bar .= "=";
        }

        $status_bar .= "] $done/$total";

        echo $status_bar;

        if ($done == $total) {
            echo "\n";
        }
    }
}
