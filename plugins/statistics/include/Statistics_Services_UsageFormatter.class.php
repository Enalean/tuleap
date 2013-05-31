<?php

/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class Statistics_Services_UsageFormatter {

    private $datas;
    private $title;
    private $stats_formatter;

    public function __construct(Statistics_Formatter $stats_formatter) {
        $this->stats_formatter = $stats_formatter;
        $this->datas           = array();
        $this->title           = array();
    }

    /**
     * Export in CSV the datas builded from SQL queries
     * @return String $content the CSV content
     */
    public function exportCSV() {
        $this->stats_formatter->clearContent();
        $this->stats_formatter->addLine(array_values($this->title));
        foreach ($this->datas as $value) {
            $this->stats_formatter->addLine(array_values($value));
        }
        return $this->stats_formatter->getCsvContent();
    }

    /**
     * Build CVS datas from SQL queries results to export them in a file
     * @param array $query_result
     * @param type $title
     */
    public function buildDatas(array $query_result, $title) {
        $this->initiateDatas($query_result);
        $this->title[] = $title;
        $this->addDefaultValuesForTitle($title);
        $this->addValuesFromQueryResultForTitle($query_result, $title);

        return $this->datas;
    }

    private function addDefaultValuesForTitle($title) {
        $ids = array_keys($this->datas);
        foreach ($ids as $id) {
            $this->datas[$id][$title] = 0;
        }
    }

    private function addValuesFromQueryResultForTitle(array $query_result, $title) {
        foreach ($query_result as $data) {
            if ($this->canAddValueFromQuery($data)) {
                $this->datas[$data['group_id']][$title] = $data['result'];
            }
        }
    }

    private function initiateDatas(array $query_result) {
        if (! empty($this->datas)) {
            return;
        }

        foreach ($query_result as $data) {
            $this->datas[$data['group_id']] = array();
        }
    }

    private function canAddValueFromQuery(array $data) {
        return array_key_exists($data['group_id'], $this->datas) && isset($data['result']);
    }
}

?>
