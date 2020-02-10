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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_PaneBuilderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $card_in_cell_presenter_builder;
    private $artifact_factory;
    private $dao;
    private $user;
    private $milestone_artifact;
    private $swimline_factory;
    private $mapping_collection;
    private $columns;

    protected function setUp() : void
    {
        parent::setUp();
        $this->card_in_cell_presenter_builder = Mockery::spy(Cardwall_CardInCellPresenterBuilder::class);
        $this->artifact_factory               = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->dao                            = \Mockery::spy(\AgileDashboard_BacklogItemDao::class);
        $this->swimline_factory               = \Mockery::spy(\Cardwall_SwimlineFactory::class);
        $this->user                           = new PFUser(['language_id' => 'en']);
        $this->milestone_artifact             = Mockery::mock(Tracker_Artifact::class);
        $this->milestone_artifact->shouldReceive('getId')->andReturn(1);
        $this->mapping_collection             = \Mockery::spy(\Cardwall_MappingCollection::class);
        $this->columns                        = \Mockery::spy(\Cardwall_OnTop_Config_ColumnCollection::class);
    }

    public function testItReturnsAnEmptyBoard() : void
    {
        $this->dao->shouldReceive('getBacklogArtifacts')->andReturns(\TestHelper::emptyDar());

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);

        $this->assertInstanceOf(\Cardwall_Board::class, $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection));
    }

    public function testItReturnsABoardWithASoloSwimline() : void
    {
        $swimline_artifact = $this->buildMockArtifactAllUserCanView();
        $swimline_artifact->shouldReceive('getChildrenForUser')->andReturns(array());

        $row = array('id' => 'the id');
        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($row)->andReturns($swimline_artifact);
        $this->dao->shouldReceive('getBacklogArtifacts')->andReturns(\TestHelper::arrayToDar($row));

        $this->swimline_factory->shouldReceive('getCells')->andReturns(array(array('cardincell_presenters' => 'something')));
        $this->card_in_cell_presenter_builder->shouldReceive('getCardInCellPresenter')->with($swimline_artifact, $swimline_artifact->getId())->andReturns(\Mockery::spy(\Cardwall_CardInCellPresenter::class));

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);
        $board = $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection);
        $this->assertCount(1, $board->swimlines);
        $this->assertInstanceOf(\Cardwall_SwimlineSolo::class, $board->swimlines[0]);
    }

    public function testItReturnsABoardWithaSwimline() : void
    {
        $child_artifact = $this->buildMockArtifactAllUserCanView();

        $swimline_artifact = $this->buildMockArtifactAllUserCanView();
        $swimline_artifact->shouldReceive('getChildrenForUser')->andReturns(array($child_artifact));

        $this->swimline_factory->shouldReceive('getCells')->andReturns(array());

        $row = array('id' => 'whatever');
        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($row)->andReturns($swimline_artifact);
        $this->dao->shouldReceive('getBacklogArtifacts')->andReturns(\TestHelper::arrayToDar($row));

        $this->card_in_cell_presenter_builder->shouldReceive('getCardInCellPresenter')->andReturns(\Mockery::spy(\Cardwall_CardInCellPresenter::class));
        $this->card_in_cell_presenter_builder->shouldReceive('getCardInCellPresenters')->andReturns(array(\Mockery::spy(\Cardwall_CardInCellPresenter::class)));

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);
        $board = $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection);
        $this->assertCount(1, $board->swimlines);
        $this->assertInstanceOf(\Cardwall_Swimline::class, $board->swimlines[0]);
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private function buildMockArtifactAllUserCanView()
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $artifact->shouldReceive('userCanView')->andReturn(true);

        return $artifact;
    }
}
