<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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

class GraphOnTrackersV5_Burndown_DataBuilder extends ChartDataBuilderV5
{
    /**
     * build burndown chart properties
     *
     * @param Burndown_Engine $engine object
     */
    public function buildProperties($engine)
    {
        parent::buildProperties($engine);

        $form_element_factory = Tracker_FormElementFactory::instance();
        $effort_field         = $form_element_factory->getFormElementById($this->chart->getFieldId());
        $type                 = $form_element_factory->getType($effort_field);

        if ($this->isValidEffortField($effort_field, $type) && $this->isValidType($type)) {
            $time_period     = new TimePeriodWithWeekEnd($this->chart->getStartDate(), $this->chart->getDuration());
            $engine->data    = $this->getBurnDownData($effort_field->getId(), $type, $time_period);
        }

        $engine->legend      = null;
        $engine->start_date  = $this->chart->getStartDate();
        $engine->duration    = $this->chart->getDuration();
    }

    protected function getBurnDownData($effort_field_id, $type, TimePeriodWithWeekEnd $time_period)
    {
        $artifact_ids = explode(',', $this->artifacts['id']);

        $sql          = "SELECT c.artifact_id AS id,
                          DATE_FORMAT(FROM_UNIXTIME(c.submitted_on), '%Y%m%d') as day,
                          cvi.value
                        FROM tracker_changeset AS c
                          INNER JOIN tracker_changeset_value AS cv ON(cv.changeset_id = c.id AND cv.field_id = " . db_ei($effort_field_id) . ")
                          INNER JOIN tracker_changeset_value_" . db_es($type) . " AS cvi ON(cvi.changeset_value_id = cv.id)
                        WHERE c.artifact_id IN  (" . db_ei_implode($artifact_ids) . ")
                        ORDER BY day, cvi.changeset_value_id DESC";

        return new GraphOnTrackersV5_Burndown_Data(db_query($sql), $artifact_ids, $time_period);
    }

    protected function isValidEffortField($effort_field, $type)
    {
        return $effort_field && $effort_field->userCanRead(UserManager::instance()->getCurrentUser());
    }

    /**
     * Autorized types for effort field type
     *
     * @var array
     */
    protected function isValidType($type)
    {
        return in_array($type, array('int', 'float'));
    }
}
