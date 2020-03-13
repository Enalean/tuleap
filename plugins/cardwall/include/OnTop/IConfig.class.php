<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
 * Provide a link between a Tracker_Artifact and a Tracker_FormElement_Field
 */
interface Cardwall_OnTop_IConfig
{

    public function getTracker();

    public function isEnabled();

    public function enable();

    public function disable();

    public function getDashboardColumns();

    public function getRendererColumns(Tracker_FormElement_Field_List $cardwall_field);

    public function getFilteredRendererColumns(Tracker_FormElement_Field_List $cardwall_field, array $filter);

    public function getMappings();

    public function getTrackers();

    public function getMappingFor(Tracker $mapping_tracker);

    public function isInColumn(
        Tracker_Artifact $artifact,
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_Column $column
    );

    public function getCardwallMappings(array $fields, Cardwall_OnTop_Config_ColumnCollection $cardwall_columns);

    public function fillMappingsWithOnTopMappings(
        Cardwall_MappingCollection $mappings,
        Cardwall_OnTop_Config_ColumnCollection $columns
    );
}
