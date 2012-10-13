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

require_once dirname(__FILE__).'/../../tracker/tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../include/SwimlineFactory.class.php';

class Cardwall_SwimLineFactoryTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->config     = mock('Cardwall_OnTop_Config');
        $this->factory    = new Cardwall_SwimlineFactory($this->config, mock('Cardwall_FieldProviders_IProvideFieldGivenAnArtifact'));
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsAndNoPresenters() {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        $presenters = array();
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsAnEmptyArrayIfThereAreNoColumnsButSomePresenters() {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        $presenters = array(mock('Cardwall_CardInCellPresenter'));
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $this->assertIdentical(array(), $swimlines);
    }
    
    public function itReturnsANestedArrayOfPresenterPresentersIfThereAreColumnsButNoPresenters() {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection(array(mock('Cardwall_Column')));
        $presenters = array();
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $expected   = array(
                          array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itAsksTheColumnIfItGoesInThere() {
        $artifact1 = anArtifact()->withId(1)->build();
        $artifact2 = anArtifact()->withId(2)->build();
        $label = $bgcolor = $fgcolor = null;
        $column1   = new Cardwall_Column(55, $label, $bgcolor, $fgcolor);
        $column2   = new Cardwall_Column(100, $label, $bgcolor, $fgcolor);
        $columns   = new Cardwall_OnTop_Config_ColumnCollection(array($column1, $column2));
        $cardincell_presenter1 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact1);
        $cardincell_presenter2 = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact2);
        
        stub($this->config)->isInColumn($artifact1, '*', $column1)->returns(true);
        stub($this->config)->isInColumn($artifact2, '*', $column2)->returns(true);
        
        $swimlines = $this->factory->getCells($columns, array($cardincell_presenter1, $cardincell_presenter2));
        $expected  = array(
                        array('cardincell_presenters' => array($cardincell_presenter1)),
                        array('cardincell_presenters' => array($cardincell_presenter2)));
        $this->assertIdentical($expected, $swimlines);
    }
    
    public function itIgnoresPresentersIfThereIsNoMatchingColumn() {
        $artifact = anArtifact()->build();
        $column = new Cardwall_Column(55, null, null, null);
        $columns  = new Cardwall_OnTop_Config_ColumnCollection();
        $columns[]= $column;
        $cardincell_presenter = stub('Cardwall_CardInCellPresenter')->getArtifact()->returns($artifact);
        
        stub($this->config)->isInColumn($artifact, '*', $column)->returns(false);

        $swimlines = $this->factory->getCells($columns, array($cardincell_presenter));
        $expected  = array(
                        array('cardincell_presenters' => array()));
        $this->assertIdentical($expected, $swimlines);
    }
}

?>
