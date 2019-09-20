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

require_once __DIR__ .'/bootstrap.php';

class Cardwall_Column_isInColumnTest extends TuleapTestCase
{


    //TODO move this to the configTest file
    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $tracker = aMockeryTracker()->withId(33)->build();
        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        $changset = new Tracker_Artifact_Changeset_Null();
        stub($this->artifact)->getTracker()->returns($tracker);
        stub($this->artifact)->getLastChangeset()->returns($changset);

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class);
        $this->field_provider = mockery_stub(\Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class)->getField($tracker)->returns($this->field);
        $dao = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $column_factory = \Mockery::spy(\Cardwall_OnTop_Config_ColumnFactory::class);
        $tracker_mapping_factory = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMappingFactory::class);

        $column_factory->shouldReceive('getDashboardColumns')->with($tracker)->andReturn(new Cardwall_OnTop_Config_ColumnCollection());

        $this->config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
    }

    public function itIsInTheCellIfTheLabelMatches()
    {
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $column   = $this->newCardwall_Column(0, 'ongoing');
        $this->assertIn($column);
    }

    public function itIsNotInTheCellIfTheLabelDoesntMatch()
    {
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $column   = $this->newCardwall_Column(0, 'done');
        $this->assertNotIn($column);
    }

    public function itIsInTheCellIfItHasNoStatusAndTheColumnHasId100()
    {
        $null_status = null;
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns($null_status);
        $column   = $this->newCardwall_Column(100, 'done');
        $this->assertIn($column);
    }

    public function itIsNotInTheCellIfItHasNoStatus()
    {
        $null_status = null;
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns($null_status);
        $column   = $this->newCardwall_Column(123, 'done');
        $this->assertNotIn($column);
    }

    public function itIsNotInTheCellIfHasANonMatchingLabelTheColumnIdIs100()
    {
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $column   = $this->newCardwall_Column(100, 'done');
        $this->assertNotIn($column);
    }

    private function assertIn($column)
    {
         $this->assertTrue($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    private function assertNotIn($column)
    {
         $this->assertFalse($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    public function newCardwall_Column($id, $label)
    {
        $header_color = 0;
        return new Cardwall_Column($id, $label, $header_color);
    }
}

class Cardwall_Column_canContainStatusTest extends TuleapTestCase
{

    private $column;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $id = 100;
        $label = $header_color = 'whatever';
        $this->column = new Cardwall_Column($id, $label, $header_color);
    }

    public function itReturnsTrueOnNoneColumnIfStatusIsNone()
    {
        $this->assertTrue($this->column->canContainStatus('None'));
    }
    public function itReturnsTrueOnNoneColumnIfStatusIsNull()
    {
        $this->assertTrue($this->column->canContainStatus(null));
    }
}
