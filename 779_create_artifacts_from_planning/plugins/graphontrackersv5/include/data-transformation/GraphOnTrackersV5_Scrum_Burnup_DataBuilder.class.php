<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class GraphOnTrackersV5_Scrum_Burnup_DataBuilder extends ChartDataBuilderV5 {

    /**
     * build burnup chart properties
     *
     * @param Burnup_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $data   = array();
        $remaining = array();
        $engine->legend = null;
        $result = array();
        $ff = Tracker_FormElementFactory::instance();
        $remaining_f = $ff->getFormElementById($this->chart->getRemainingField());
        $done_f      = $ff->getFormElementById($this->chart->getDoneField());
        $ids = array_map(create_function('$a', 'return $a["id"];'), $this->artifacts);
        if ($remaining_f && $remaining_f->userCanRead(user_getid()) && 
            $done_f && $done_f->userCanRead(user_getid())) 
        {
            $sql = "SELECT c.artifact_id AS id, 
                           TO_DAYS(FROM_UNIXTIME(submitted_on)) - TO_DAYS(FROM_UNIXTIME(0)) as day, 
                           f.value as remaining,
                           done_f.value as done
                    FROM tracker_changeset AS c 
                         INNER JOIN tracker_changeset_value AS v ON(v.changeset_id = c.id)
                         INNER JOIN tracker_field_int_value AS f ON(f.field_id = v.field_id and v.value_id = f.id)
                         INNER JOIN tracker_changeset_value AS done_v ON(done_v.changeset_id = c.id)
                         INNER JOIN tracker_field_int_value AS done_f ON(done_f.field_id = done_v.field_id and done_v.value_id = done_f.id)
                    WHERE c.artifact_id IN (". implode(',', $ids) .")
                      AND v.field_id = $remaining_f->id
                      AND done_v.field_id = $done_f->id";
            //syntax($sql, 'sql');
            $res = db_query($sql);
            $data = $this->extractDataFromResult($res, $ids, 'done');
            $remaining = $this->extractDataFromResult($res, $ids, 'remaining');
        }
        $engine->data = $data;
        $engine->remaining = $remaining;
        return $data;
    }
    
    protected function extractDataFromResult($res, $ids, $column) {
        $data = array();
        if ($res && db_numrows($res)) {
            db_reset_result($res);
            while($d = db_fetch_array($res)) {
                if (!isset($data[$d['day']])) {
                    $data[$d['day']] = array();
                    foreach($ids as $id) {
                        $data[$d['day']][$id] = 0;
                    }
                }
                $data[$d['day']][$d['id']] += $d[$column];
            }
            ksort($data);
            $previous = array();
            foreach($data as $k => $d) {
                if (count($previous)) {
                    foreach($d as $id => $v) {
                        if ($v == 0 && $previous[$id]) {
                            $data[$k][$id] = $previous[$id];
                        }
                    }
                }
                $previous = $data[$k];
            }
        }
        return $data;
    }
}
?>
