<?php
/*
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2013. Jtekt Europe SAS.
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

class GraphOnTrackersV5_CumulativeFlow_DataBuilder extends ChartDataBuilderV5 {

    const MAX_STEPS = 75;
    protected $timeFiller;
    protected $startDate;
    protected $stopDate;
    protected $scale;
    protected $nbSteps;
    protected $labels;
    protected $observed_field_id;

    /**
     * build cumulative_flow chart properties
     *
     * @param CumulativeFlow_Engine $engine object
     */
    public function buildProperties($engine) {
        parent::buildProperties($engine);

        $form_element_factory = Tracker_FormElementFactory::instance();
        $observed_field       = $form_element_factory->getFormElementById($this->chart->getFieldId());
        $type                 = $form_element_factory->getType($observed_field);
        $this->observed_field_id = $observed_field->getId();
        $this->timeFiller = array(GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_DAY => 3600*24,
            GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_WEEK => 3600*24*7,
            GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_MONTH => 3600*24*30.45
        );
        $this->startDate = $this->chart->getStartDate();
        $this->stopDate = $this->chart->getStopDate() ? $this->chart->getStopDate() : time();
        $this->scale = $this->chart->getScale();
        $this->nbSteps = ceil(($this->stopDate - $this->startDate)/$this->timeFiller[$this->scale]);

        if ($this->isValidObservedField($observed_field, $type) && $this->isValidType($type)) {
            $engine->data    = $this->getCumulativeFlowData($engine);
        }

        $engine->legend      = null;
        $engine->start_date  = $this->chart->getStartDate();
        $engine->scale        = $this->chart->getScale();
        $engine->stop_date   = $this->chart->getStopDate();
    }

    protected function getCumulativeFlowData($engine) {
        $result = array();

        if($this->nbSteps > GraphOnTrackersV5_CumulativeFlow_DataBuilder::MAX_STEPS) {
            //STHAP
            //$GLOBALS['Response']->addFeedback('error', "Please choose a smaller period, or increase the scale");
            $engine->error = new ErrorChart($GLOBALS['Language']->getText('plugin_tracker', 'unable_to_render_the_chart'),
            $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'error_too_many_points'), 400, 200);
        } else {
            $tmpResult = $this->initEmptyResultArrayFromField($engine);

            for ($i = 0 ; $i <= $this->nbSteps; $i++ ) {
                $timestamp = $this->startDate + ($i * $this->timeFiller[$this->scale]) ;
                $changesets = $this->getLastChangesetsBefore($timestamp);

                // Count the number of occurence of each label of the source field at the given date.
                // Return {Label, count}
                $sql = "SELECT l.bindvalue_id, count(*) as count
    	FROM `tracker_changeset` as c
    	JOIN `tracker_changeset_value` v ON (v.changeset_id = c.id AND v.field_id = $this->observed_field_id )
    	JOIN tracker_changeset_value_list l ON (l.changeset_value_id = v.id)
    	WHERE artifact_id in (". $this->artifacts['id'] .")
    	AND c.id IN (". implode(',', $changesets) .")
    	GROUP BY l.bindvalue_id";
                $res = db_query($sql);
                while($data = db_fetch_array($res)) {
                   $tmpResult[$timestamp][$data['bindvalue_id']] = intval($data['count']);
                }

                $result[$timestamp] = $this->switchArrayKeys($tmpResult[$timestamp]);
            }
        }
        return $this->filterEmptyLines($result);;
    }

    protected function isValidObservedField($observed_field, $type) {
        return $observed_field && $observed_field->userCanRead(UserManager::instance()->getCurrentUser());
    }

    /**
     * Autorized types for observed field type
     *
     * @var array
     */
    protected function isValidType($type) {
        return in_array($type, array('sb', 'msb', 'cb'));
    }

    /**
     *
     * Fetch the colors, and initialize an empty result array. => $tempData[timestamp][label_id] = 0
     * @param int $field_id ID of the observed field
     * @return array $resultArray Initialized array for this graph
     */
    private function initEmptyResultArrayFromField($engine) {

            //Return {Label, r, g, b}
            $sql = "SELECT val.id, val.label, deco.red, deco.green, deco.blue, deco.tlp_color_name
    FROM  tracker_field_list_bind_static_value val
    LEFT JOIN tracker_field_list_bind_decorator deco ON (val.id = deco.value_id)
    WHERE val.field_id = $this->observed_field_id
    ORDER BY val.rank";
            $res = db_query($sql);
            $this->labels[100] = $GLOBALS['Language']->getText('global','none');
            $engine->colors[$this->labels[100]] = array(null, null, null);
            $resultArray = array();
            while($data = db_fetch_array($res)) {
               $engine->colors[$data['label']] = $this->getColorForJPGraph($data);
               $this->labels[$data['id']] = $data['label'];
               for ($i = 0 ; $i <= $this->nbSteps; $i++ ) {
                   $timestamp = $this->startDate + ($i * $this->timeFiller[$this->scale]) ;
                   $resultArray[$timestamp][100] =  0;
                   $resultArray[$timestamp][$data['id']] =  0;
               }
            }
            foreach ($resultArray as $timestamp => $values) {
                $resultArray[$timestamp] = array_reverse($resultArray[$timestamp], true);
            }
        return $resultArray;
    }

    /**
     *
     * Get the the last changeset BEFORE the timestamp for each artifact
     * @param int $beforeTimestamp
     * @return $changesets array of changeset_id
     */
    private function getLastChangesetsBefore($timestamp) {
        $sql = "SELECT MAX(id) as id
            FROM `tracker_changeset` c
            WHERE c.submitted_on < $timestamp
            AND c.artifact_id IN (". $this->artifacts['id'] .")
            GROUP BY artifact_id";

        $res = db_query($sql);
        $changesets = array();
        while($data = db_fetch_array($res)) {
           $changesets[] = $data['id'];
        }
        return $changesets;
    }

    /**
     *
     * Switch field_value_id to field_value_label
     * @param array $tmpArray Array to convert
     * @return array $result Switched array
     */
    private function switchArrayKeys($tmpArray) {
        $result = array();
        foreach ($tmpArray as $k => $v) {
            $result[$this->labels[$k]] = $v;
        }
        return $result;
    }

    /**
     * Filter empty lines from chart data
     * protected for testing purpose
     * @param  array $array array to filter
     *                      array structure must be: $array[timestamp][bind_value]= count
     * @return array        filtered array
     */
     protected function filterEmptyLines(array $array) {
        $lines_with_values = array();
        foreach ($array as $entry) {
            $lines_with_values += array_filter($entry);
        }
        array_walk($array, array($this, 'keepLinesWithValues'), $lines_with_values);
        return $array;
    }
    private function keepLinesWithValues(&$entry, $key, $lines_with_values) {
        $entry = array_intersect_key($entry, $lines_with_values);
    }
}
?>
