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

class GraphOnTrackersV5_Evolution_DataBuilder extends ChartDataBuilderV5 {
    /**
     * build evolution chart properties
     *
     * @param Evolution_Engine $engine object
     */
    public function buildProperties($engine) {
        parent::buildProperties($engine);

        $form_element_factory = Tracker_FormElementFactory::instance();
        $observed_field       = $form_element_factory->getFormElementById($this->chart->getFieldId());
        $type                 = $form_element_factory->getType($observed_field);
        
        if ($this->isValidObservedField($observed_field, $type) && $this->isValidType($type)) {
            $engine->data    = $this->getEvolutionData($observed_field->getId(), $type);
        }
        
        $engine->legend      = null;
        $engine->start_date  = $this->chart->getStartDate();
        $engine->unit        = $this->chart->getUnit();
        $engine->nb_step     = $this->chart->getNbStep();
    }
    
    protected function getEvolutionData($observed_field_id, $type) {
        $artifact_ids = explode(',', $this->artifacts['id']);
        $sql = "SELECT c.artifact_id AS id, TO_DAYS(FROM_UNIXTIME(submitted_on)) - TO_DAYS(FROM_UNIXTIME(0)) as day, value
                            FROM tracker_changeset AS c 
                                 INNER JOIN tracker_changeset_value AS cv ON(cv.changeset_id = c.id AND cv.field_id = ". $observed_field_id . ")";
        
        $sql .= " INNER JOIN tracker_changeset_value_$type AS cvi ON(cvi.changeset_value_id = cv.id)";
        $sql .= " WHERE c.artifact_id IN (". implode(',', $artifact_ids) .")";
        //echo "<br />\n<pre>";var_dump($this);echo "</pre><br />\n";
        return null;
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
