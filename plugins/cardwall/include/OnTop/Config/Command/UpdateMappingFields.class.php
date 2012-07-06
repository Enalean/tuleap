<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once CARDWALL_BASE_DIR .'/OnTop/Config/Command.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/ColumnDao.class.php';

/**
 * Update a column for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_UpdateMappingFields extends Cardwall_OnTop_Config_Command {

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker $tracker, Cardwall_OnTop_ColumnMappingFieldDao $dao, TrackerFactory $tracker_factory, Tracker_FormElementFactory $form_element_factory) {
        parent::__construct($tracker);
        $this->dao                  = $dao;
        $this->tracker_factory      = $tracker_factory;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request) {
        if (is_array($request->get('mapping_field'))) {
            $mapping_fields = $this->getMappingFields();
            foreach ($request->get('mapping_field') as $mapping_tracker_id => $field_id) {
                $mapping_tracker = $this->tracker_factory->getTrackerById($mapping_tracker_id);
                $field           = $this->form_element_factory->getFieldById($field_id);
                $this->save($mapping_fields, $mapping_tracker, $field);
            }
        }
    }

    /**
     * @return array
     */
    private function getMappingFields() {
        $mapping_fields = array();
        $mapping_fields_rows = $this->dao->searchMappingFields($this->tracker->getId());
        foreach ($mapping_fields_rows as $row) {
            $mapping_fields[$row['tracker_id']] = $row['field_id'];
        }
        return $mapping_fields;
    }

    /**
     * @return void
     */
    private function save(array $mapping_fields, Tracker $mapping_tracker = null, Tracker_FormElement $field = null) {
        if ($this->canSaveNewField($mapping_fields, $mapping_tracker, $field) && $this->dao->save($this->tracker->getId(), $mapping_tracker->getId(), $field->getId())) {
            $GLOBALS['Response']->addFeedback('info', 'Mapping on '. $mapping_tracker->getName() .' changed to '. $field->getLabel());
        }
    }

    /**
     * @return bool
     */
    private function canSaveNewField(array $mapping_fields, Tracker $mapping_tracker = null, Tracker_FormElement $field = null) {
        return $mapping_tracker &&
            $field &&
            $field->getTracker() == $mapping_tracker &&
            ( !isset($mapping_fields[$mapping_tracker->getId()]) || $mapping_fields[$mapping_tracker->getId()] != $field->getId());
    }
}
?>
