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

require_once 'common/user/UserManager.class.php';
require_once 'GraphOnTrackersV5_Burndown_Data.class.php';

class GraphOnTrackersV5_Burndown_DataBuilder extends ChartDataBuilderV5 {

    /**
     * build burndown chart properties
     *
     * @param Burndown_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);

        $fef          = Tracker_FormElementFactory::instance();
        $effort_field = $fef->getFormElementById($this->chart->getFieldId());
        $type         = $fef->getType($effort_field);
        
        $artifact_ids = explode(',', $this->artifacts['id']);
        
        if ($effort_field && $effort_field->userCanRead(UserManager::instance()->getCurrentUser())) {
            $sql = "SELECT c.artifact_id AS id, TO_DAYS(FROM_UNIXTIME(submitted_on)) - TO_DAYS(FROM_UNIXTIME(0)) as day, value
                    FROM tracker_changeset AS c 
                         INNER JOIN tracker_changeset_value AS cv ON(cv.changeset_id = c.id AND cv.field_id = ". $effort_field->getId() . ")";
            if ($type == 'int') {
                $sql .= " INNER JOIN tracker_changeset_value_int AS cvi ON(cvi.changeset_value_id = cv.id)";
            } else {
                $sql .= " INNER JOIN tracker_changeset_value_float AS cvi ON(cvi.changeset_value_id = cv.id)";
            }
            $sql .= " WHERE c.artifact_id IN (". implode(',', $artifact_ids) .")";
            $res = db_query($sql);
            $engine->data = new GraphOnTrackersV5_Burndown_Data($res, $artifact_ids);
        }
        
        $engine->legend     = null;
        $engine->start_date = $this->chart->getStartDate();
        $engine->duration   = $this->chart->getDuration();
    }

}
?>
