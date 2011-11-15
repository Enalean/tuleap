<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/user/UserManager.class.php');

class GraphOnTrackersV5_Scrum_Burndown_DataBuilder extends ChartDataBuilderV5 {

    /**
     * build burndown chart properties
     *
     * @param Burndown_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $data   = array();
        $engine->legend = null;
        $result = array();
        $fef = Tracker_FormElementFactory::instance();
        $effort_field = $fef->getFormElementById($this->chart->getFieldId());
        $start_date = $this->chart->getStartDate();

        $day = 24 * 60 * 60;
        $start_day = round($start_date / $day);
        $artifact_ids = explode(',', $this->artifacts['id']);
        
        if ($effort_field && $effort_field->userCanRead(UserManager::instance()->getCurrentUser())) {
            $sql = "SELECT c.artifact_id AS id, TO_DAYS(FROM_UNIXTIME(submitted_on)) - TO_DAYS(FROM_UNIXTIME(0)) as day, value
                    FROM tracker_changeset AS c 
                         INNER JOIN tracker_changeset_value AS cv ON(cv.changeset_id = c.id AND cv.field_id = ". $effort_field->getId() . ")
                         INNER JOIN tracker_changeset_value_int AS cvi ON(cvi.changeset_value_id = cv.id)
                    WHERE c.artifact_id IN (". implode(',', $artifact_ids) .")";
            $res = db_query($sql);
            $dbdata = array();
            $minday=0;
            $maxday=0;
            while ($d = db_fetch_array($res)) {
                if (!isset($dbdata[$d['day']])) {
                    $dbdata[$d['day']] = array();
                }
                $dbdata[$d['day']][$d['id']] = $d['value'];
                if ($d['day'] > $maxday) $maxday=$d['day'];
                if ($d['day'] < $minday) $minday=$d['day'];
            }
            
            for ($day=$start_day; $day<=$maxday; $day++) {
                if (!isset($data[$start_date])) {
                    $data[$start_date]= array();
                }
            }
            // We assume here that SQL returns effort value order by changeset_id ASC
            // so we only keep the last value (possible to change effort several times a day)

            foreach($artifact_ids as $aid) {
                for ($day=$minday; $day<=$maxday; $day++) {
                    if ($day < $start_date) {
                        if (isset($dbdata[$day][$aid])) {
                            $data[$start_date][$aid] = $dbdata[$day][$aid];
                        }
                    } else if ($day == $start_day) {
                        if (isset($dbdata[$day][$aid])) {
                            $data[$day][$aid] = $dbdata[$day][$aid];
                        } else {
                            $data[$day][$aid] = 0;
                        }
                    } else {
                        if (isset($dbdata[$day][$aid])) {
                            $data[$day][$aid] = $dbdata[$day][$aid];
                        } else {
                            // No update this day: get value from previous day
                            $data[$day][$aid] = $data[$day-1][$aid];
                        }
                    }
                } 
            }
        }
        $engine->duration = $this->chart->getDuration();
        $engine->data = $data;

    }

}
?>
