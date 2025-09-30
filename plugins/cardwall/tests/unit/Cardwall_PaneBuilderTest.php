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

namespace Tuleap\Cardwall;

use Cardwall_Board;
use Cardwall_CardInCellPresenter;
use Cardwall_CardInCellPresenterBuilder;
use Cardwall_MappingCollection;
use Cardwall_PaneBoardBuilder;
use Cardwall_Swimline;
use Cardwall_SwimlineFactory;
use Cardwall_SwimlineSolo;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_PaneBuilderTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    private Cardwall_CardInCellPresenterBuilder&MockObject $card_in_cell_presenter_builder;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private BacklogItemDao&MockObject $dao;
    private PFUser $user;
    private Artifact $milestone_artifact;
    private Cardwall_SwimlineFactory&MockObject $swimline_factory;
    private Cardwall_MappingCollection&MockObject $mapping_collection;
    private ColumnCollection $columns;

    #[\Override]
    protected function setUp(): void
    {
        $this->card_in_cell_presenter_builder = $this->createMock(Cardwall_CardInCellPresenterBuilder::class);
        $this->artifact_factory               = $this->createMock(Tracker_ArtifactFactory::class);
        $this->dao                            = $this->createMock(BacklogItemDao::class);
        $this->swimline_factory               = $this->createMock(Cardwall_SwimlineFactory::class);
        $this->user                           = UserTestBuilder::buildWithDefaults();
        $this->milestone_artifact             = ArtifactTestBuilder::anArtifact(1)->build();
        $this->mapping_collection             = $this->createMock(Cardwall_MappingCollection::class);
        $this->columns                        = new ColumnCollection();
    }

    public function testItReturnsAnEmptyBoard(): void
    {
        $this->dao->method('getBacklogArtifacts')->willReturn([]);

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);

        self::assertInstanceOf(Cardwall_Board::class, $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection));
    }

    public function testItReturnsABoardWithASoloSwimline(): void
    {
        $swimline_artifact = $this->buildMockedArtifactAllUserCanView();
        $swimline_artifact->method('getChildrenForUser')->willReturn([]);

        $row = ['id' => 'the id'];
        $this->artifact_factory->method('getInstanceFromRow')->with($row)->willReturn($swimline_artifact);
        $this->dao->method('getBacklogArtifacts')->willReturn([$row]);

        $this->swimline_factory->method('getCells')->willReturn([['cardincell_presenters' => 'something']]);
        $this->card_in_cell_presenter_builder->method('getCardInCellPresenter')->with($swimline_artifact, $swimline_artifact->getId())->willReturn($this->createMock(Cardwall_CardInCellPresenter::class));

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);
        $board        = $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection);
        self::assertCount(1, $board->swimlines);
        self::assertInstanceOf(Cardwall_SwimlineSolo::class, $board->swimlines[0]);
    }

    public function testItReturnsABoardWithaSwimline(): void
    {
        $child_artifact = $this->buildMockedArtifactAllUserCanView();

        $swimline_artifact = $this->buildMockedArtifactAllUserCanView();
        $swimline_artifact->method('getChildrenForUser')->willReturn([$child_artifact]);

        $this->swimline_factory->method('getCells')->willReturn([]);

        $row = ['id' => 'whatever'];
        $this->artifact_factory->method('getInstanceFromRow')->with($row)->willReturn($swimline_artifact);
        $this->dao->method('getBacklogArtifacts')->willReturn([$row]);

        $presenter = $this->createMock(Cardwall_CardInCellPresenter::class);
        $presenter->method('getId');
        $this->card_in_cell_presenter_builder->method('getCardInCellPresenter')->willReturn($presenter);
        $this->card_in_cell_presenter_builder->method('getCardInCellPresenters')->willReturn([$presenter]);

        $pane_builder = new Cardwall_PaneBoardBuilder($this->card_in_cell_presenter_builder, $this->artifact_factory, $this->dao, $this->swimline_factory);
        $board        = $pane_builder->getBoard($this->user, $this->milestone_artifact, $this->columns, $this->mapping_collection);
        self::assertCount(1, $board->swimlines);
        self::assertInstanceOf(Cardwall_Swimline::class, $board->swimlines[0]);
    }

    private function buildMockedArtifactAllUserCanView(): Artifact&MockObject
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(1);
        $artifact->method('userCanView')->willReturn(true);

        return $artifact;
    }
}
