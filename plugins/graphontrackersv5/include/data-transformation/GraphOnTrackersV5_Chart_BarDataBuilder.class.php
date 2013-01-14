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

class GraphOnTrackersV5_Chart_BarDataBuilder extends ChartDataBuilderV5 {
    /**
     * build pie chart properties
     *
     * @param Pie_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $engine->data   = array();
        $engine->xaxis  = null;
        $engine->labels = null;
        $engine->legend = null;
        $result = array();
        $ff = Tracker_FormElementFactory::instance();
        $af = $ff->getFormElementById($this->chart->getField_base());
        if ($af && $af->userCanRead()) {
            $select_group = $from_group = $group_group = $order_group = '';
            if ($this->chart->getField_group()) {
                $gf = $ff->getFormElementById($this->chart->getField_group());
                if ($gf && $gf->userCanRead()) {
                    $select_group = ', '. $gf->getQuerySelect();
                    $from_group   = '  '. $gf->getQueryFrom();
                    $group_group  = ', '. $gf->getQueryGroupBy();
                    $order_group  = ', '. $gf->getQueryOrderby();
                }
            }
            $select = " SELECT count(a.id) AS nb, ". $af->getQuerySelectWithDecorator() . $select_group;
            $from   = " FROM tracker_artifact AS a 
                             INNER JOIN tracker_changeset AS c ON (c.artifact_id = a.id) " . 
                             $af->getQueryFromWithDecorator() . 
                             $from_group;
            $where  = " WHERE a.id IN (". $this->artifacts['id'] .") 
                          AND c.id IN (". $this->artifacts['last_changeset_id'] .") ";
            $sql = $select . $from . $where . ' GROUP BY ' . $af->getQueryGroupBy() . $group_group . ' ORDER BY '. $af->getQueryOrderby() . $order_group;
           //echo($sql);
            $res = db_query($sql);
            while($data = db_fetch_array($res)) {
                if ($data[$af->name] !== null) {
                    if ($select_group) {
                        $engine->colors[$data[$af->name]] =  array($data['red'], $data['green'], $data['blue']);
                        $engine->data[$data[$af->name]][$data[$gf->name]]   = $data['nb'];
                        if($data[$gf->name] !== null) {
                            $engine->xaxis[$data[$gf->name]] = $gf->fetchRawValue($data[$gf->name]);
                            $engine->labels[$data[$gf->name]] = $gf->fetchRawValue($data[$gf->name]);
                        } else {
                            $engine->xaxis[$data[$gf->name]] = $GLOBALS['Language']->getText('global','none');
                            $engine->labels[$data[$gf->name]] = $GLOBALS['Language']->getText('global','none');
                        }
                    } else {
                        $engine->colors[] =  array($data['red'], $data['green'], $data['blue']);
                        $engine->data[]   = $data['nb'];
                    }
                    $engine->legend[$data[$af->name]] = $af->fetchRawValue($data[$af->name]);
                } else {
                    if ($select_group) {
                        $engine->data[$data[$af->name]][$data[$gf->name]]   = $data['nb'];
                        if($data[$gf->name] !== null) {
                            $engine->xaxis[$data[$gf->name]] = $gf->fetchRawValue($data[$gf->name]);
                            $engine->labels[$data[$gf->name]] = $gf->fetchRawValue($data[$gf->name]);
                        } else {
                            $engine->xaxis[$data[$gf->name]] = $GLOBALS['Language']->getText('global','none');
                            $engine->labels[$data[$gf->name]] = $GLOBALS['Language']->getText('global','none');
                        }
                    } else {
                        $engine->data[]   = $data['nb'];
                    }
                    $engine->legend[$data[$af->name]] = $GLOBALS['Language']->getText('global','none');
                }
            }
            if ($select_group) {
                $engine->xaxis = array_values($engine->xaxis);
            }
        }
        return $result;
    }
}
?>
