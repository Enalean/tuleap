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


/**
 * Create a MappingField for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_DeleteMappingFields extends Cardwall_OnTop_Config_Command
{

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $dao;

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $value_dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Array of Cardwall_OnTop_Config_TrackerMapping
     */
    private $existing_mappings;

    public function __construct(
        Tracker $tracker,
        Cardwall_OnTop_ColumnMappingFieldDao $dao,
        Cardwall_OnTop_ColumnMappingFieldValueDao $value_dao,
        TrackerFactory $tracker_factory,
        array $existing_mappings
    ) {
        parent::__construct($tracker);
        $this->dao               = $dao;
        $this->value_dao         = $value_dao;
        $this->tracker_factory   = $tracker_factory;
        $this->existing_mappings = $existing_mappings;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request)
    {
        if (is_array($request->get('custom_mapping'))) {
            foreach ($request->get('custom_mapping') as $mapping_tracker_id => $is_custom) {
                $mapping_tracker = $this->tracker_factory->getTrackerById($mapping_tracker_id);
                if ($this->canDelete($is_custom, $mapping_tracker) && $this->delete($mapping_tracker)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_mapping_removed', array($mapping_tracker->getName())));
                }
            }
        }
    }

    /**
     * @psalm-assert-if-true !null $mapping_tracker
     */
    private function canDelete($is_custom, ?Tracker $mapping_tracker = null)
    {
        return !$is_custom
            && $mapping_tracker
            && $this->mappingExists($mapping_tracker->getId());
    }

    private function mappingExists($mapping_tracker_id)
    {
        return isset($this->existing_mappings[$mapping_tracker_id])
            && $this->existing_mappings[$mapping_tracker_id] instanceof Cardwall_OnTop_Config_TrackerMappingFreestyle;
    }

    private function delete(Tracker $mapping_tracker)
    {
        return $this->dao->delete($this->tracker->getId(), $mapping_tracker->getId())
            && $this->value_dao->delete($this->tracker->getId(), $mapping_tracker->getId());
    }
}
