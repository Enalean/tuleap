<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

require_once dirname(__FILE__) .'/bootstrap.php';
require_once dirname(__FILE__) .'/../../agiledashboard/include/AgileDashboard/BacklogItemDao.class.php';

class Cardwall_PaneBuilderTest extends TuleapTestCase
{

    private $card_in_cell_presenter_builder;
    private $artifact_factory;
    private $dao;
    private $user;
    private $milestone_artifact;
    private $swimline_factory;
    private $mapping_collection;
    private $columns;

    public function setUp()
    {
        parent::setUp();
        $this->card_in_cell_presenter_builder = Mockery::spy(Cardwall_CardInCellPresenterBuilder::class);
        $this->artifact_factory               = mock('Tracker_ArtifactFactory');
        $this->dao                            = mock('AgileDashboard_BacklogItemDao');
        $this->swimline_factory               = mock('Cardwall_SwimlineFactory');
        $this->user                           = aUser()->build();
        $this->milestone_artifact             = anArtifact()->withId(1)->build();
        $this->mapping_collection             = mock('Cardwall_MappingCollection');
        $this->columns                        = mock('Cardwall_OnTop_Config_ColumnCollection');
    }

    public function itReturnsAnEmptyBoard()
    {
        stub($this->dao)->getBacklogArtifacts()->returnsEmptyDar();

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);

        $this->assertIsA($pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection), 'Cardwall_Board');
    }

    public function itReturnsABoardWithaSoloSwimline()
    {
        $swimline_artifact = aMockArtifact()->withId('the id')->allUsersCanView()->build();
        stub($swimline_artifact)->getChildrenForUser()->returns(array());

        $row = array('id' => 'the id');
        stub($this->artifact_factory)->getInstanceFromRow($row)->returns($swimline_artifact);
        stub($this->dao)->getBacklogArtifacts()->returnsDar($row);

        stub($this->swimline_factory)->getCells()->returns(array(array('cardincell_presenters' => 'something')));
        stub($this->card_in_cell_presenter_builder)->getCardInCellPresenter($swimline_artifact, $swimline_artifact->getId())->returns(mock('Cardwall_CardInCellPresenter'));

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);
        $board = $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection);
        $this->assertCount($board->swimlines, 1);
        $this->assertIsA($board->swimlines[0], 'Cardwall_SwimlineSolo');
    }

    public function itReturnsABoardWithaSwimline()
    {
        $child_artifact = aMockArtifact()->withId('child')->allUsersCanView()->build();

        $swimline_artifact = aMockArtifact()->withId('whatever')->allUsersCanView()->build();
        stub($swimline_artifact)->getChildrenForUser()->returns(array($child_artifact));

        stub($this->swimline_factory)->getCells()->returns(array());

        $row = array('id' => 'whatever');
        stub($this->artifact_factory)->getInstanceFromRow($row)->returns($swimline_artifact);
        stub($this->dao)->getBacklogArtifacts()->returnsDar($row);

        stub($this->card_in_cell_presenter_builder)->getCardInCellPresenter()->returns(mock('Cardwall_CardInCellPresenter'));
        stub($this->card_in_cell_presenter_builder)->getCardInCellPresenters()->returns(array(mock('Cardwall_CardInCellPresenter')));

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);
        $board = $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection);
        $this->assertCount($board->swimlines, 1);
        $this->assertIsA($board->swimlines[0], 'Cardwall_Swimline');
    }
}
