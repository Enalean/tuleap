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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class GraphOnTrackers_Scrum_Burndown_DataBuilder extends ChartDataBuilder {
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $history = $this->getHistory();
        $sum = array();
        foreach($history as $h) {
            foreach($h as $d => $v) {
                if (!isset($sum[$d])) {
                    $sum[$d] = 0;
                }
                $sum[$d] += $v;
            }
        }
        ksort($sum);
        $engine->duration = $this->chart->getDuration();
        $engine->data = $sum;
        /*
        $engine->data = array(
            $start => 200, 
            ($start + 1 * $day) => 190, 
            ($start + 2 * $day) => 190,
            ($start + 3 * $day) => 180,
            ($start + 4 * $day) => 170,
            ($start + 5 * $day) => 180,
            ($start + 6 * $day) => 170,
            ($start + 7 * $day) => 150,
            ($start + 8 * $day) => 192,
            ($start + 8 * $day) => 191,
            ($start + 9 * $day) => 190,
            ($start + 10 * $day) => 189,
            ($start + 11 * $day) => 188,
            ($start + 12 * $day) => 187,
            ($start + 13 * $day) => 186,
            ($start + 14 * $day) => 185,
            ($start + 15 * $day) => 184,
            ($start + 16 * $day) => 183,
            ($start + 17 * $day) => 182,
            ($start + 18 * $day) => 181,
            ($start + 19 * $day) => 180,
            ($start + 20 * $day) => 179,
            ($start + 21 * $day) => 83,
        );
        /**/
    }
    
    function sumRemainingEffort($date) {
    }
    
    function getHistory() {
        require_once('common/tracker/Artifact.class.php');
        require_once('common/tracker/ArtifactField.class.php');

        $field_name = 'remaining_effort';
        //retrieve history of the field
        $field_history = array();
        $sql = "SELECT field_name, artifact_id, artifact_history_id, date, new_value, old_value
                FROM artifact_history
                WHERE artifact_id IN (". implode(',', $this->artifacts) .")
                  AND field_name = '$field_name'
                ORDER BY artifact_id, artifact_history_id DESC";
        $res = db_query($sql);
        while($data = db_fetch_array($res)) {
            $field_history[$data['artifact_id']][] = $data;
        }
        
        //retrieve actual value of the field
        $field_value = array();
        $at = new ArtifactType(
            project_get_object($this->chart->getGraphicReport()->getGroupId()),
            $this->chart->getGraphicReport()->getAtid()
        );
        foreach($this->artifacts as $aid) {
            $a = new Artifact($at,$aid);
            $field_value[$aid] = $a->getValue($field_name);
        }
        
        //build total history
        $history = array();
        $day = 60 * 60 * 24;
        $start = min(strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME'])), $this->chart->getStartDate() + $this->chart->getDuration() * $day);
        $end = $this->chart->getStartDate();
        foreach($this->artifacts as $aid) {
            $history[$aid] = array();
            //initialize with current value
            for($x = $start ; $x >= $end - $day; $x -= $day) {
                $history[$aid][$x] = $field_value[$aid];
            }
            
            //replace with history
            if (isset($field_history[$aid])) {
                foreach($field_history[$aid] as $h) {
                    $dh = strtotime(date('Y-m-d', (int)$h['date']));
                    for($x = $dh ; $x >= $end - $day; $x -= $day) {
                        $history[$aid][$x] = $h['old_value'];
                    }
                    //new dbug($this->history[$aid]);echo '<hr>';
                    $history[$aid][$dh] = $h['new_value'];
                }
            }
        }
        return $history;
    }
}
?>
