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
        $columns = array(mock('Cardwall_Column'));
        $presenters   = array();
        $swimlines = $factory->getCellsOfSwimline($columns, $presenters);
        $expected = array(
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itAsksTheColumnIfItGoesInThere() {
        $factory = new Cardwall_SwimlineFactory();
        $label_1   = 'ongoing';
        $label_2   = 'review';
        $artifact1 = stub('Tracker_Artifact')->getStatus()->returns($label_1);
        $artifact2 = stub('Tracker_Artifact')->getStatus()->returns($label_2);
        $columns = array(stub('Cardwall_Column')->isInColumn($artifact1)->returns(true),
                         stub('Cardwall_Column')->isInColumn($artifact2)->returns(true));
        $cardincell_presenter1 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact1);
        $cardincell_presenter2 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact2);
        
        $swimlines = $factory->getCellsOfSwimline($columns, array($cardincell_presenter1, $cardincell_presenter2));
        $expected = array(
                        array('cardincell_presenters' => array($cardincell_presenter1)),
                        array('cardincell_presenters' => array($cardincell_presenter2)));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itIgnoresPresentersIfThereIsNoMatchingColumn() {
        $factory = new Cardwall_SwimlineFactory();
        $artifact_label = 'in progress';
        $artifact = stub('Tracker_Artifact')->getStatus()->returns($artifact_label);
        $columns = array(stub('Cardwall_Column')->isInColumn($artifact)->returns(false));
        $cardincell_presenter = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);

        $swimlines = $factory->getCellsOfSwimline($columns, array($cardincell_presenter));
        $expected = array(
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
}

?>
