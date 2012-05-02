<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

require_once('ChartDataBuilderV5.class.php');

class GraphOnTrackersV5_Chart_PieDataBuilder extends ChartDataBuilderV5 {
    /**
     * build pie chart properties
     *
     * @param Pie_Engine $engine object
     */
    function buildProperties($engine) {
        
        parent::buildProperties($engine);
        $engine->data   = array();
        $engine->legend = null;
        $result = array();
        $ff = Tracker_FormElementFactory::instance();
        $af = $ff->getFormElementById($this->chart->getField_base());
        if ($af && $af->userCanRead()) {
            $select = " SELECT count(a.id) AS nb, ". $af->getQuerySelect();
            $from   = " FROM tracker_artifact AS a INNER JOIN tracker_changeset AS c ON (c.artifact_id = a.id) ". $af->getQueryFrom();
            $where  = " WHERE a.id IN (". $this->artifacts['id'] .") 
                          AND c.id IN (". $this->artifacts['last_changeset_id'] .") ";
            $sql = $select . $from . $where . ' GROUP BY ' . $af->getQueryGroupBy();
            $res = db_query($sql);
            while($data = db_fetch_array($res)) {
                if ($data[$af->name] !== null) {
                    $engine->data[]   = $data['nb'];
                    $engine->legend[] = $af->fetchRawValue($data[$af->name]);
                } else {
                    $engine->data[]   = $data['nb'];
                    $engine->legend[] = $GLOBALS['Language']->getText('global','none');
                }
            }
        }
        return $result;
    }
}
?>
