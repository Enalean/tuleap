<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Statistics_Formatter.class.php';

class Statistics_Services_UsageFormatter
{
    /** @const number of bytes in a MegaByte */
    public const BYTES_NUMBER_IN_MB = 1000;

    /** @var array */
    private $datas;

    /** @var array */
    private $title;

    /** @var Statistics_Formatter */
    private $stats_formatter;

    public const GROUP_ID = 'group_id';
    public const VALUES   = 'result';

    public function __construct(Statistics_Formatter $stats_formatter)
    {
        $this->stats_formatter = $stats_formatter;
        $this->datas           = [];
        $this->title           = [];
    }

    /**
     * Export in CSV the datas builded from SQL queries
     * @return String $content the CSV content
     */
    public function exportCSV()
    {
        $this->stats_formatter->clearContent();
        $this->stats_formatter->addLine(array_values($this->title));
        foreach ($this->datas as $value) {
            $this->stats_formatter->addLine(array_values($value));
        }
        return $this->stats_formatter->getCsvContent();
    }

    /**
     * Build CSV datas from SQL queries results to export them in a file
     * @param array|\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface $query_result
     */
    public function buildDatas($query_result, string $title)
    {
        $this->initiateDatas($query_result);
        $this->title[] = $title;
        $this->addDefaultValuesForTitle($title);
        $this->addValuesFromQueryResultForTitle($query_result, $title);

        return $this->datas;
    }

    /**
     * Format a query result containing size to put them in MegaBytes
     *
     * @param array|\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface $query_results
     */
    public function formatSizeInMegaBytes($query_results)
    {
        $resized_results = [];
        foreach ($query_results as $result) {
            $result[self::VALUES] = round($result[self::VALUES] / self::BYTES_NUMBER_IN_MB);
            $resized_results[]    = $result;
        }

        return $resized_results;
    }

    private function addDefaultValuesForTitle($title)
    {
        $ids = array_keys($this->datas);
        foreach ($ids as $id) {
            $this->datas[$id][$title] = 0;
        }
    }

    private function addValuesFromQueryResultForTitle($query_result, $title)
    {
        foreach ($query_result as $data) {
            if ($this->canAddValueFromQuery($data)) {
                $this->datas[$data[self::GROUP_ID]][$title] = $data[self::VALUES];
            }
        }
    }

    private function initiateDatas($query_result)
    {
        if (! empty($this->datas)) {
            return;
        }

        foreach ($query_result as $data) {
            $this->datas[$data[self::GROUP_ID]] = [];
        }
    }

    private function canAddValueFromQuery(array $data)
    {
        return array_key_exists($data[self::GROUP_ID], $this->datas) && isset($data[self::VALUES]);
    }
}
