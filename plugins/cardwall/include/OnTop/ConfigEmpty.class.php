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

require_once 'Dao.class.php';
require_once 'Config/ColumnFactory.class.php';
require_once 'Config/TrackerMappingFactory.class.php';
require_once 'Config/ColumnCollection.class.php';
require_once CARDWALL_BASE_DIR. '/Mapping.class.php';
require_once 'IConfig.class.php';

/**
 * Manage configuration of a cardwall on top of a tracker
 */
class Cardwall_OnTop_ConfigEmpty implements Cardwall_OnTop_IConfig {

    public function getTracker() {
        return null;
    }

    public function isEnabled() {
        return false;
    }

    public function enable() {
        return false;
    }

    public function disable() {
        return false;
    }

    /**
     * Get Frestyle columns for Cardwall_OnTop, or status columns if none
     * 
     * @param Tracker $tracker
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getDashboardColumns() {
        return false;
    }

    /**
     * Get Columns from the values of a $field
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getRendererColumns(Tracker_FormElement_Field_List $cardwall_field) {
        return false;
    }
    
    public function getMappings() {
        return false;
    }

    public function getTrackers() {
        return false;
    }
    
    /**
     * @param Tracker $mapping_tracker
     * 
     * @return Cardwall_OnTop_Config_TrackerMapping
     */
    public function getMappingFor(Tracker $mapping_tracker) {
        return null;
    }
    
    private function isMappedTo($tracker, $artifact_status, Cardwall_Column $column) {
        return false;
    }

    public function isInColumn(Tracker_Artifact                                     $artifact, 
                               Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider, 
                               Cardwall_Column                                      $column) {
        return false;
    }

    /**
     * Get the column/field/value mappings by duck typing the colums labels 
     * with the values of the given fields
     *
     * @param array $fields array of Tracker_FormElement_Field_Selectbox
     *
     * @return Cardwall_MappingCollection
     */
    public function getCardwallMappings(array $fields, Cardwall_OnTop_Config_ColumnCollection $cardwall_columns) {
        return false;
    }
    
    private function fillMappingsByDuckType(Cardwall_MappingCollection             $mappings, 
                                            array                                  $fields, 
                                            Cardwall_OnTop_Config_ColumnCollection $columns) {
        return false;
    }

    public function fillMappingsWithOnTopMappings(Cardwall_MappingCollection             $mappings, 
                                                  Cardwall_OnTop_Config_ColumnCollection $columns) {
    }

    

}
?>
