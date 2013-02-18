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
require_once('DataBuilderV5.class.php');
require_once('ChartDataBuilderV5.class.php');
require_once(TRACKER_BASE_DIR .'/Tracker/Artifact/Tracker_ArtifactFactory.class.php');

class GraphOnTrackersV5_Chart_GanttDataBuilder extends ChartDataBuilderV5 {

    /**
     * build Gantt chart properties
     *
     * @param Bar_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $engine->title      = $this->chart->getTitle();
        $engine->description= $this->chart->getDescription();
        $engine->scale      = $this->chart->getScale();
        $engine->asOfDate   = $this->chart->getAs_of_date();
        $af = Tracker_FormElementFactory::instance()->getFormElementById($this->chart->getSummary());
        if ($af) {
            $engine->summary_label = $af->getLabel();
        }
        $this->buildData($engine);
    }

    /**
     * build bar chart data
     *
     * @param Gantt_Engine object
     * @return array data array
     */ 
    function buildData($engine) {
        $engine->data = array();
        
        $ff = Tracker_FormElementFactory::instance();
        $field_start      = $this->chart->getField_start()      ? $ff->getFormElementById($this->chart->getField_start())      : null;
        $field_due        = $this->chart->getField_due()        ? $ff->getFormElementById($this->chart->getField_due())        : null;
        $field_finish     = $this->chart->getField_finish()     ? $ff->getFormElementById($this->chart->getField_finish())     : null;
        $field_percentage = $this->chart->getField_percentage() ? $ff->getFormElementById($this->chart->getField_percentage()) : null;
        $field_righttext  = $this->chart->getField_righttext()  ? $ff->getFormElementById($this->chart->getField_righttext())  : null;
        $field_summary    = $this->chart->getSummary()          ? $ff->getFormElementById($this->chart->getSummary())          : null;
        $af = Tracker_ArtifactFactory::instance();
        $changesets = explode(',', $this->artifacts['last_changeset_id']);
        foreach(explode(',', $this->artifacts['id']) as $i => $aid) {
            if ($artifact = $af->getArtifactByid($aid)) {
                if ($changeset = $artifact->getChangeset($changesets[$i])) {
                    $data = array(
                        'id'       => $aid,
                        'summary'  => '#'. $aid,
                        'start'    => 0,
                        'due'      => 0,
                        'finish'   => 0,
                        'progress' => 0,
                        'right'    => '',
                        'hint'     => '#'. $aid,
                        'links'    => TRACKER_BASE_URL.'/?aid='. $aid,
                    );
                    
                    if ($field_start) {
                        $data['start'] = $field_start->fetchRawValueFromChangeset($changeset);
                    }
                    
                    if ($field_due) {
                        $data['due'] = $field_due->fetchRawValueFromChangeset($changeset);
                    }
                    
                    if ($field_finish) {
                        $data['finish'] = $field_finish->fetchRawValueFromChangeset($changeset);
                    }
                    
                    if ($field_percentage) {
                        $data['progress'] = $field_percentage->fetchRawValueFromChangeset($changeset);
                    }
                    
                    if ($field_righttext) {
                        $data['right'] = $field_righttext->fetchRawValueFromChangeset($changeset);
                    }
                    
                    if ($field_summary) {
                        $data['hint'] = $data['summary'] = $field_summary->fetchRawValueFromChangeset($changeset);
                    }
                    
                    if ($data['progress'] < 0) {
                        $data['progress'] = 0;
                    } else if ($data['progress'] > 100) {
                        $data['progress'] = 1;
                    } else {
                        $data['progress'] = $data['progress'] / 100;
                    }
                    $engine->data[] = $data;
                }
            }
        }
        usort($engine->data, array($this, 'sortByDate'));
        return $engine->data;
    }

    private function sortByDate($a, $b) {
        $a_date = $this->getSuitableDateForSorting($a);
        $b_date = $this->getSuitableDateForSorting($b);

        return strcmp($a_date, $b_date);
    }

    private function getSuitableDateForSorting($artifact_data) {
        $date = $artifact_data['start'];
        // a milestone has no start date, only a end one
        if (! $date) {
            $date = $artifact_data['finish'];
        }
        return $date;
    }
}
?>
