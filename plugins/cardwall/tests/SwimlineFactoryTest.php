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

require_once dirname(__FILE__).'/../include/SwimlineFactory.class.php';

class Cardwall_SwimLineFactoryTest extends TuleapTestCase {
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsAndNoPresenters() {
        $factory = new Cardwall_SwimlineFactory();
        $columns = array();
        $presenters   = array();
        $swimlines = $factory->getCellsOfSwimline($columns, $presenters);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsButSomePresenters() {
        $factory = new Cardwall_SwimlineFactory();
        $columns = array();
        $presenters   = array(mock('Cardwall_CardInCellPresenter'));
        $swimlines = $factory->getCellsOfSwimline($columns, $presenters);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsANestedArrayOfPresenterPresentersIfThereAreColumnsButNoPresenters() {
        $factory = new Cardwall_SwimlineFactory();
        $id = $label = $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column($id, $label, $bgcolor, $fgcolor));
        $presenters   = array();
        $swimlines = $factory->getCellsOfSwimline($columns, $presenters);
        $expected = array(
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itReturnsANestedArrayOfPresenterPresenters() {
        $factory = new Cardwall_SwimlineFactory();
        $label   = 'ongoing';
        $id = $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column($id, $label, $bgcolor, $fgcolor));
        $artifact = stub('Tracker_Artifact')->getStatus()->returns($label);
        $cardincell_presenter = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);
        
        $swimlines = $factory->getCellsOfSwimline($columns, array($cardincell_presenter));
        $expected = array(
                        array('cardincell_presenters' => array($cardincell_presenter)));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itSortsPresentersIntoColumsBasedOnTheMatchBetweenArtifactAndFieldLabel() {
        $factory = new Cardwall_SwimlineFactory();
        $label_1   = 'ongoing';
        $label_2   = 'review';
        $id = $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column($id, $label_1, $bgcolor, $fgcolor),
                         new Cardwall_Column($id, $label_2, $bgcolor, $fgcolor));
        $artifact1 = stub('Tracker_Artifact')->getStatus()->returns($label_1);
        $artifact2 = stub('Tracker_Artifact')->getStatus()->returns($label_2);
        $cardincell_presenter1 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact1);
        $cardincell_presenter2 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact2);
        
        $swimlines = $factory->getCellsOfSwimline($columns, array($cardincell_presenter1, $cardincell_presenter2));
        $expected = array(
                        array('cardincell_presenters' => array($cardincell_presenter1)),
                        array('cardincell_presenters' => array($cardincell_presenter2)));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itIgnoresPresentersIfThereIsNoMatchingLabel() {
        $factory = new Cardwall_SwimlineFactory();
        $column_label   = 'ongoing';
        $id = $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column($id, $column_label, $bgcolor, $fgcolor));
        $artifact_label = 'in progress';
        $artifact = stub('Tracker_Artifact')->getStatus()->returns($artifact_label);
        $cardincell_presenter = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);

        $swimlines = $factory->getCellsOfSwimline($columns, array($cardincell_presenter));
        $expected = array(
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itPutsPresentersWithNullStatusIntoTheColumnWithId100() {
        $factory = new Cardwall_SwimlineFactory();
        $column_label   = '';
        $bgcolor = $fgcolor = 0;
        $columns = array(new Cardwall_Column(100, $column_label, $bgcolor, $fgcolor), 
                         new Cardwall_Column(200, 'in progress', $bgcolor, $fgcolor));
        $null_status = null;
        $artifact = stub('Tracker_Artifact')->getStatus()->returns($null_status);
        $cardincell_presenter = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);
        
        $swimlines = $factory->getCellsOfSwimline($columns, array($cardincell_presenter));
        $expected = array(
                        array('cardincell_presenters' => array($cardincell_presenter)),
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
}

class Cardwall_SwimlineFactory_isArtifactInCellTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->factory = new Cardwall_SwimlineFactory();
        $this->artifact = mock('Tracker_Artifact');
        $this->field = mock('Tracker_FormElement_Field_MultiSelectbox');
        $this->field_provider = stub('Cardwall_FieldProviders_IProvideFieldGivenAnArtifact')->getField($this->artifact)->returns($this->field);
    }
    
    public function itIsInTheCellIfTheLabelMatches() {
        stub($this->field)->getValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $id = $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column($id, 'ongoing', $bgcolor, $fgcolor);
        $this->assertTrue($this->factory->isArtifactInCell2($this->artifact, $column, $this->field_provider));
    }
    
    public function itIsNotInTheCellIfTheLabelDoesntMatch() {
        stub($this->field)->getValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $id = $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column($id, 'done', $bgcolor, $fgcolor);
        $this->assertFalse($this->factory->isArtifactInCell2($this->artifact, $column, $this->field_provider));
    }

    public function itIsInTheCellIfItHasNoStatusAndTheColumnHasId100() {
        $null_status = null;
        stub($this->field)->getValueFor($this->artifact->getLastChangeset())->returns($null_status);
        $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column(100, 'done', $bgcolor, $fgcolor);
        $this->assertTrue($this->factory->isArtifactInCell2($this->artifact, $column, $this->field_provider));
    }

    public function itIsNotInTheCellIfItHasNoStatus() {
        $null_status = null;
        stub($this->field)->getValueFor($this->artifact->getLastChangeset())->returns($null_status);
        $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column(123, 'done', $bgcolor, $fgcolor);
        $this->assertFalse($this->factory->isArtifactInCell2($this->artifact, $column, $this->field_provider));
    }

    public function itIsNotInTheCellIfHasANonMatchingLabelTheColumnIdIs100() {
        stub($this->field)->getValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column(100, 'done', $bgcolor, $fgcolor);
        $this->assertFalse($this->factory->isArtifactInCell2($this->artifact, $column, $this->field_provider));
    }
    
    public function itIsInTheCellIfTheLabelMatches_old() {
        $factory = new Cardwall_SwimlineFactory();
        $artifact = stub('Tracker_Artifact')->getStatus()->returns('ongoing');
        $id = $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column($id, 'ongoing', $bgcolor, $fgcolor);
        $this->assertTrue($factory->isArtifactInCell($artifact, $column));
    }
    public function itIsNotInTheCellIfTheLabelDoesntMatch_old() {
        $factory = new Cardwall_SwimlineFactory();
        $artifact = stub('Tracker_Artifact')->getStatus()->returns('ongoing');
        $id = $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column($id, 'done', $bgcolor, $fgcolor);
        $this->assertFalse($factory->isArtifactInCell($artifact, $column));
    }
    
    public function itIsInTheCellIfItHasNoStatusAndTheColumnHasId100_old() {
        $factory = new Cardwall_SwimlineFactory();
        $null_status = null;
        $artifact = stub('Tracker_Artifact')->getStatus()->returns($null_status);
        $bgcolor = $fgcolor = 0;
        $column   = new Cardwall_Column(100, 'unimportant', $bgcolor, $fgcolor);
        $this->assertTrue($factory->isArtifactInCell($artifact, $column));
    }
}
?>
