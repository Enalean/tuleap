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

use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Tracker;

/**
 * Manage configuration of a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config implements Cardwall_OnTop_IConfig // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
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
     * @var ColumnFactory
     */
    private $column_factory;

    /**
     * @var Cardwall_OnTop_Config_TrackerMappingFactory
     */
    private $tracker_mapping_factory;

    /**
     * @var Cardwall_OnTop_Config_TrackerMapping[]
     */
    private $cached_mappings = [];

    public function __construct(
        Tracker $tracker,
        Cardwall_OnTop_Dao $dao,
        ColumnFactory $column_factory,
        Cardwall_OnTop_Config_TrackerMappingFactory $tracker_mapping_factory,
    ) {
        $this->tracker                 = $tracker;
        $this->dao                     = $dao;
        $this->column_factory          = $column_factory;
        $this->tracker_mapping_factory = $tracker_mapping_factory;
    }

    #[\Override]
    public function getTracker()
    {
        return $this->tracker;
    }

    #[\Override]
    public function isEnabled()
    {
        return $this->dao->isEnabled($this->tracker->getId());
    }

    public function isFreestyleEnabled()
    {
        return $this->dao->isFreestyleEnabled($this->tracker->getId());
    }

    #[\Override]
    public function enable()
    {
        return $this->dao->enable($this->tracker->getId());
    }

    #[\Override]
    public function disable()
    {
        return $this->dao->disable($this->tracker->getId());
    }

    /**
     * Get Frestyle columns for Cardwall_OnTop, or status columns if none
     *
     * @return ColumnCollection
     */
    #[\Override]
    public function getDashboardColumns()
    {
        return $this->column_factory->getDashboardColumns($this->tracker);
    }

    /**
     * Get Columns from the values of a $field
     * @return ColumnCollection
     */
    #[\Override]
    public function getRendererColumns(ListField $cardwall_field)
    {
        return $this->column_factory->getRendererColumns($cardwall_field);
    }

    /**
     * @return ColumnCollection
     */
    #[\Override]
    public function getFilteredRendererColumns(ListField $cardwall_field, array $filter)
    {
        return $this->column_factory->getFilteredRendererColumns($cardwall_field, $filter);
    }

    /**
     *
     * @return Cardwall_OnTop_Config_TrackerMapping[]
     */
    #[\Override]
    public function getMappings()
    {
        if (! isset($this->cached_mappings[$this->tracker->getId()])) {
            $this->cached_mappings[$this->tracker->getId()] = $this->tracker_mapping_factory->getMappings($this->tracker, $this->getDashboardColumns());
        }
        return $this->cached_mappings[$this->tracker->getId()];
    }

    #[\Override]
    public function getTrackers()
    {
        return $this->tracker_mapping_factory->getTrackers($this->tracker);
    }

    /**
     *
     * @return Cardwall_OnTop_Config_TrackerMapping | null
     */
    #[\Override]
    public function getMappingFor(Tracker $mapping_tracker)
    {
        $mappings = $this->getMappings();
        return isset($mappings[$mapping_tracker->getId()]) ? $mappings[$mapping_tracker->getId()] : null;
    }

    #[\Override]
    public function isInColumn(
        Artifact $artifact,
        Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider,
        Cardwall_Column $column,
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
     * @param ListField[] $fields
     *
     * @return Cardwall_MappingCollection
     */
    #[\Override]
    public function getCardwallMappings(array $fields, ColumnCollection $cardwall_columns)
    {
        $mappings = new Cardwall_MappingCollection();
        $this->fillMappingsByDuckType($mappings, $fields, $cardwall_columns);
        $this->fillMappingsWithOnTopMappings($mappings, $cardwall_columns);
        return $mappings;
    }

    private function fillMappingsByDuckType(
        Cardwall_MappingCollection $mappings,
        array $fields,
        ColumnCollection $columns,
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

    #[\Override]
    public function fillMappingsWithOnTopMappings(
        Cardwall_MappingCollection $mappings,
        ColumnCollection $columns,
    ) {
        foreach ($this->getMappings() as $field_mapping) {
            foreach ($field_mapping->getValueMappings() as $value_mapping) {
                $column = $columns->getColumnById($value_mapping->getColumnId());
                if ($column) {
                    $value        = $value_mapping->getValue();
                    $mapped_field = $field_mapping->getField();
                    if ($mapped_field !== null) {
                        $mappings->add(new Cardwall_Mapping($column->id, $mapped_field->getId(), $value->getId()));
                    }
                }
            }
        }
    }
}
