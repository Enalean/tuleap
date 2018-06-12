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

require_once dirname(__FILE__) .'/../bootstrap.php';

class Cardwall_OnTop_ConfigTest extends TuleapTestCase {

    public function itAsksForMappingByGivenListOfColumns() {
        $tracker                 = aTracker()->build();
        $swimline_tracker        = aTracker()->build();
        $dao                     = mock('Cardwall_OnTop_Dao');
        $column_factory          = mock('Cardwall_OnTop_Config_ColumnFactory');
        $tracker_mapping_factory = mock('Cardwall_OnTop_Config_TrackerMappingFactory');

        $columns = array('of', 'columns');
        stub($column_factory)->getDashboardColumns($tracker)->returns($columns);
        stub($tracker_mapping_factory)->getMappings($tracker, $columns)->once()->returns('whatever');

        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        $this->assertEqual('whatever', $config->getMappings());
    }
}

class Cardwall_OnTop_Config_getMappingForTest extends TuleapTestCase {

    public function itReturnsNullIfThereIsNoMapping() {
        $tracker                 = aTracker()->withId(1)->build();
        $mapping_tracker         = aTracker()->withId(2)->build();

        $dao                     = mock('Cardwall_OnTop_Dao');
        $column_factory          = mock('Cardwall_OnTop_Config_ColumnFactory');
        $tracker_mapping_factory = mock('Cardwall_OnTop_Config_TrackerMappingFactory');
        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        $this->assertNull($config->getMappingFor($mapping_tracker));
    }

    public function itReturnsTheCorrespondingMapping() {
        $tracker                 = aTracker()->withId(1)->build();
        $mapping_tracker         = aTracker()->withId(99)->build();

        $dao                     = mock('Cardwall_OnTop_Dao');
        $column_factory          = mock('Cardwall_OnTop_Config_ColumnFactory');
        $mapping = mock('Cardwall_OnTop_Config_TrackerMapping');
        $tracker_mapping_factory = stub('Cardwall_OnTop_Config_TrackerMappingFactory')->getMappings()->returns(array(99 => $mapping));
        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        $this->assertEqual($mapping, $config->getMappingFor($mapping_tracker));
    }
}

class Cardwall_OnTop_Config_IsInColumnTest extends TuleapTestCase {
    private $artifact;
    private $config;

    public function setUp() {
        parent::setUp();

        $value_none       = new Tracker_FormElement_Field_List_Bind_StaticValue(100, 'None', '', 0, 0);
        $value_todo       = new Tracker_FormElement_Field_List_Bind_StaticValue(101, 'Todo', '', 0, 0);
        $value_inprogress = new Tracker_FormElement_Field_List_Bind_StaticValue(102, 'In Progress', '', 0, 0);
        $value_done       = new Tracker_FormElement_Field_List_Bind_StaticValue(103, 'Done', '', 0, 0);

        $mapping_none    = new Cardwall_OnTop_Config_ValueMapping($value_none, 10);
        $mapping_todo    = new Cardwall_OnTop_Config_ValueMapping($value_todo, 10);
        $mapping_ongoing = new Cardwall_OnTop_Config_ValueMapping($value_inprogress, 11);
        $mapping_done    = new Cardwall_OnTop_Config_ValueMapping($value_done, 12);

        $this->value_mappings = array(
            100 => $mapping_none,
            101 => $mapping_todo,
            102 => $mapping_ongoing,
            103 => $mapping_done,
        );
        $this->mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(mock('Tracker'), array(), $this->value_mappings, aSelectBoxField()->build());

        $this->config   = partial_mock('Cardwall_OnTop_Config', array('getMappingFor'));

        $changset = new Tracker_Artifact_Changeset_Null();

        $this->artifact = aMockArtifact()
                ->withTracker(aTracker()->build())
                ->withlastChangeset($changset)
                ->build();
    }

    public function itIsNotInColumnWhenNoFieldAndNoMapping() {
        stub($this->config)->getMappingFor()->returns(null);
        $field_provider = mock('Cardwall_FieldProviders_CustomFieldRetriever');
        $column         = new Cardwall_Column(10, 'In ', '');

        $this->assertFalse($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function itIsMappedToAColumnWhenTheStatusValueMatchColumnMapping() {
        stub($this->config)->getMappingFor()->returns($this->mapping);

        $field          = stub('Tracker_FormElement_Field_List')->getFirstValueFor()->returns('In Progress');
        $field_provider = stub('Cardwall_FieldProviders_CustomFieldRetriever')->getField()->returns($field);
        $column         = new Cardwall_Column(11, 'Ongoing', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function itIsNotMappedWhenTheStatusValueDoesntMatchColumnMapping() {
        stub($this->config)->getMappingFor()->returns($this->mapping);

        $field          = stub('Tracker_FormElement_Field_List')->getFirstValueFor()->returns('Todo');
        $field_provider = stub('Cardwall_FieldProviders_CustomFieldRetriever')->getField()->returns($field);
        $column         = new Cardwall_Column(11, 'Ongoing', '');

        $this->assertFalse($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function itIsMappedToAColumnWhenStatusIsNullAndNoneIsMappedToColumn() {
        stub($this->config)->getMappingFor()->returns($this->mapping);

        $field          = stub('Tracker_FormElement_Field_List')->getFirstValueFor()->returns(null);
        $field_provider = stub('Cardwall_FieldProviders_CustomFieldRetriever')->getField()->returns($field);
        $column         = new Cardwall_Column(10, 'Todo', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function itIsMappedToColumnIfStatusMatchColumn() {
        stub($this->config)->getMappingFor()->returns($this->mapping);

        $field          = stub('Tracker_FormElement_Field_List')->getFirstValueFor()->returns('Ongoing');
        $field_provider = stub('Cardwall_FieldProviders_CustomFieldRetriever')->getField()->returns($field);
        $column         = new Cardwall_Column(11, 'Ongoing', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function itIsMappedToColumnIfStatusIsNullAndMatchColumnNone() {
        stub($this->config)->getMappingFor()->returns($this->mapping);

        $field          = stub('Tracker_FormElement_Field_List')->getFirstValueFor()->returns(null);
        $field_provider = stub('Cardwall_FieldProviders_CustomFieldRetriever')->getField()->returns($field);
        $column         = new Cardwall_Column(100, 'Ongoing', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }
}

?>
