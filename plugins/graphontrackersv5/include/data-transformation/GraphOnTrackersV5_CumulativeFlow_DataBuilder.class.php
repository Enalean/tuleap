<?php
/*
 * Copyright (c) Xerox, 2013. All Rights Reserved.
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

require_once 'common/user/UserManager.class.php';

class GraphOnTrackersV5_CumulativeFlow_DataBuilder extends ChartDataBuilderV5 {
    
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
        
        if ($this->isValidObservedField($observed_field, $type) && $this->isValidType($type)) {
            $engine->data    = $this->getCumulativeFlowData($engine, $observed_field->getId(), $type);
        }
        
        $engine->legend      = null;
        $engine->start_date  = $this->chart->getStartDate();
        $engine->unit        = $this->chart->getUnit();
        $engine->stop_date   = $this->chart->getStopDate();
    }
    
    protected function getCumulativeFlowData($engine, $observed_field_id, $type) {
        $result = array();
        $timeFiller = array(3600*24, 3600*24*7, 3600*24*30.45);
        $artifact_ids = explode(',', $this->artifacts['id']);
        
        $start = $this->chart->getStartDate();
        $stop = $this->chart->getStopDate() | time();
        $unit = $this->chart->getUnit();
        $nbSteps = ceil(($stop - $start)/$timeFiller[$unit]);
        
        for ($i = 0 ; $i <= $nbSteps; $i++ ) {
            $timestamp = $start + ($i * $timeFiller[$unit]) ;
            $sql = "SELECT val2.label, r.count, deco.red, deco.green, deco.blue
FROM  tracker_field_list_bind_static_value val2
LEFT JOIN tracker_field_list_bind_decorator deco ON (val2.id = deco.value_id)
LEFT JOIN (
	SELECT label, count(*) as count
FROM tracker_field_list_bind_static_value val,  tracker_changeset_value_list l 
WHERE l.bindvalue_id = val.id
AND changeset_value_id IN (
	SELECT v.id
	FROM `tracker_changeset` as c, `tracker_changeset_value` v
	WHERE c.id = v.changeset_id
	AND v.field_id = ". $observed_field_id . "
	AND artifact_id in (". implode(',', $artifact_ids) .")
	AND submitted_on = ( 
		SELECT MAX(submitted_on) 
		FROM `tracker_changeset` c2
		WHERE c2.artifact_id = c.artifact_id
		AND c2.submitted_on < ". $timestamp ."
		)
)
GROUP BY label
ORDER BY rank
) as r
ON r.label = val2.label
WHERE val2.field_id = ". $observed_field_id;

            $res = db_query($sql);
            
            $result[$timestamp] = array();
            while($data = db_fetch_array($res)) {
               $engine->colors[$data['label']] = $this->getColor($data);
               $result[$timestamp][$data['label']] =  $data['count'] | 0; //Switch null for 0
            }
        }
        
        return $result;
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
        return in_array($type, array('sb'));
    }

}
?>
