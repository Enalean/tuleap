<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * Manage configuration of a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config implements Cardwall_OnTop_IConfig
{

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Cardwall_OnTop_Dao
     */
    private $dao;

    /**
     * @var Cardwall_OnTop_Config_ColumnFactory
     */
    private $column_factory;

    /**
     * @var Cardwall_OnTop_Config_TrackerMappingFactory
     */
    private $tracker_mapping_factory;

    /**
     * @var Cardwall_OnTop_Config_TrackerMapping[]
     */
    private $cached_mappings = array();

    public function __construct(
        Tracker $tracker,
        Cardwall_OnTop_Dao $dao,
        Cardwall_OnTop_Config_ColumnFactory $column_factory,
        Cardwall_OnTop_Config_TrackerMappingFactory $tracker_mapping_factory
    ) {
        $this->tracker                 = $tracker;
        $this->dao                     = $dao;
        $this->column_factory          = $column_factory;
        $this->tracker_mapping_factory = $tracker_mapping_factory;
    }

    public function getTracker()
    {
        return $this->tracker;
    }

    public function isEnabled()
    {
        return $this->dao->isEnabled($this->tracker->getId());
    }

    public function isFreestyleEnabled()
    {
        return $this->dao->isFreestyleEnabled($this->tracker->getId());
    }

    public function enable()
    {
        return $this->dao->enable($this->tracker->getId());
    }

    public function disable()
    {
        return $this->dao->disable($this->tracker->getId());
    }

    /**
     * Get Frestyle columns for Cardwall_OnTop, or status columns if none
     *
     * @param Tracker $tracker
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getDashboardColumns()
    {
        return $this->column_factory->getDashboardColumns($this->tracker);
    }

    /**
     * Get Columns from the values of a $field
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getRendererColumns(Tracker_FormElement_Field_List $cardwall_field)
    {
        return $this->column_factory->getRendererColumns($cardwall_field);
    }

    /**
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getFilteredRendererColumns(Tracker_FormElement_Field_List $cardwall_field, array $filter)
    {
        return $this->column_factory->getFilteredRendererColumns($cardwall_field, $filter);
    }

    /**
     *
     * @return Cardwall_OnTop_Config_TrackerMapping[]
     */
    public function getMappings()
    {
        if (! isset($this->cached_mappings[$this->tracker->getId()])) {
            $this->cached_mappings[$this->tracker->getId()] = $this->tracker_mapping_factory->getMappings($this->tracker, $this->getDashboardColumns());
        }
        return $this->cached_mappings[$this->tracker->getId()];
    }

    public function getTrackers()
    {
        return $this->tracker_mapping_factory->getTrackers($this->tracker);
    }

    /**
     *
     * @return Cardwall_OnTop_Config_TrackerMapping | null
     */
    public function getMappingFor(Tracker $mapping_tracker)
    {
        $mappings = $this->getMappings();
        return isset($mappings[$mapping_tracker->getId()]) ? $mappings[$mapping_tracker->getId()] : null;
    }

    public function isInColumn(
        Tracker_Artifact $artifact,
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_Column $column
    ) {
        $artifact_status = null;
        $field           = $field_provider->getField($artifact->getTracker());
        if ($field) {
            $last_changeset = $artifact->getLastChangeset();
            if ($last_changeset) {
                $artifact_status = $field->getFirstValueFor($last_changeset);
            }
        }
        return $column->canContainStatus($artifact_status, $this->getMappingFor($artifact->getTracker()));
    }

    /**
     * Get the column/field/value mappings by duck typing the colums labels
     * with the values of the given fields
     *
     * @param array $fields array of Tracker_FormElement_Field_Selectbox
     *
     * @return Cardwall_MappingCollection
     */
    public function getCardwallMappings(array $fields, Cardwall_OnTop_Config_ColumnCollection $cardwall_columns)
    {
        $mappings = new Cardwall_MappingCollection();
        $this->fillMappingsByDuckType($mappings, $fields, $cardwall_columns);
        $this->fillMappingsWithOnTopMappings($mappings, $cardwall_columns);
        return $mappings;
    }

    private function fillMappingsByDuckType(
        Cardwall_MappingCollection $mappings,
        array $fields,
        Cardwall_OnTop_Config_ColumnCollection $columns
    ) {
        foreach ($fields as $status_field) {
            foreach ($status_field->getVisibleValuesPlusNoneIfAny() as $value) {
                $column = $columns->getColumnByLabel($value->getLabel());
                if ($column) {
                    $mappings->add(new Cardwall_Mapping($column->id, $status_field->getId(), $value->getId()));
                }
            }
        }
        return $mappings;
    }

    public function fillMappingsWithOnTopMappings(
        Cardwall_MappingCollection $mappings,
        Cardwall_OnTop_Config_ColumnCollection $columns
    ) {
        foreach ($this->getMappings() as $field_mapping) {
            foreach ($field_mapping->getValueMappings() as $value_mapping) {
                $column = $columns->getColumnById($value_mapping->getColumnId());
                if ($column) {
                    $value = $value_mapping->getValue();
                    $mapped_field = $field_mapping->getField();
                    if ($mapped_field !== null) {
                        $mappings->add(new Cardwall_Mapping($column->id, $mapped_field->getId(), $value->getId()));
                    }
                }
            }
        }
    }
}
