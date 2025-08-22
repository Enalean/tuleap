<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Tracker;

/**
 * Manage configuration of a cardwall on top of a tracker
 */
class Cardwall_OnTop_ConfigEmpty implements Cardwall_OnTop_IConfig // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function getTracker()
    {
        return null;
    }

    public function isEnabled()
    {
        return false;
    }

    public function enable()
    {
        return false;
    }

    public function disable()
    {
        return false;
    }

    /**
     * Get Frestyle columns for Cardwall_OnTop, or status columns if none
     *
     * @return ColumnCollection
     */
    public function getDashboardColumns()
    {
        return new ColumnCollection();
    }

    /**
     * Get Columns from the values of a $field
     * @return ColumnCollection
     */
    public function getRendererColumns(ListField $cardwall_field)
    {
        return new ColumnCollection();
    }

    public function getFilteredRendererColumns(ListField $cardwall_field, array $filter)
    {
        return new ColumnCollection();
    }

    public function getMappings()
    {
        return false;
    }

    public function getTrackers()
    {
        return false;
    }

    /**
     *
     * @return Cardwall_OnTop_Config_TrackerMapping
     */
    public function getMappingFor(Tracker $mapping_tracker)
    {
        return null;
    }

    public function isInColumn(
        Artifact $artifact,
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_Column $column,
    ) {
        return false;
    }

    /**
     * Get the column/field/value mappings by duck typing the colums labels
     * with the values of the given fields
     *
     * @param ListField[] $fields
     *
     * @return Cardwall_MappingCollection
     */
    public function getCardwallMappings(array $fields, ColumnCollection $cardwall_columns)
    {
        return false;
    }

    public function fillMappingsWithOnTopMappings(
        Cardwall_MappingCollection $mappings,
        ColumnCollection $columns,
    ) {
    }
}
