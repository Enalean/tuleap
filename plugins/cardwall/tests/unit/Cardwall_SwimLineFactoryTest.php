<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Cardwall_CardInCellPresenter;
use Cardwall_Column;
use Cardwall_FieldProviders_IProvideFieldGivenAnArtifact;
use Cardwall_OnTop_Config;
use Cardwall_OnTop_Config_ColumnCollection;
use Cardwall_OnTop_Config_ColumnFreestyleCollection;
use Cardwall_SwimlineFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class Cardwall_SwimLineFactoryTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Cardwall_OnTop_Config&MockObject $config;
    private Cardwall_SwimlineFactory $factory;

    protected function setUp(): void
    {
        $this->config  = $this->createMock(Cardwall_OnTop_Config::class);
        $this->factory = new Cardwall_SwimlineFactory($this->config, $this->createMock(Cardwall_FieldProviders_IProvideFieldGivenAnArtifact::class));
    }

    public function testItReturnsAnEmptyArrayIfThereAreNoColumnsAndNoPresenters(): void
    {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        $presenters = [];
        $swimlines  = $this->factory->getCells($columns, $presenters);
        self::assertSame([], $swimlines);
    }

    public function testItReturnsAnEmptyArrayIfThereAreNoColumnsButSomePresenters(): void
    {
        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        $presenters = [$this->createMock(Cardwall_CardInCellPresenter::class)];
        $swimlines  = $this->factory->getCells($columns, $presenters);
        self::assertSame([], $swimlines);
    }

    public function testItReturnsANestedArrayOfPresenterPresentersIfThereAreColumnsButNoPresenters(): void
    {
        $mocked_column = $this->createMock(Cardwall_Column::class);
        $mocked_column->method('getId')->willReturn(44);
        $mocked_column->method('isAutostacked')->willReturn(true);

        $columns    = new Cardwall_OnTop_Config_ColumnFreestyleCollection([$mocked_column]);
        $presenters = [];
        $swimlines  = $this->factory->getCells($columns, $presenters);
        $expected   = [['column_id' => 44, 'column_stacked' => true, 'cardincell_presenters' => []]];
        self::assertSame($expected, $swimlines);
    }

    public function testItAsksTheColumnIfItGoesInThere(): void
    {
        $artifact1             = ArtifactTestBuilder::anArtifact(1)->build();
        $artifact2             = ArtifactTestBuilder::anArtifact(2)->build();
        $label                 = $bgcolor = null;
        $column1               = new Cardwall_Column(55, $label, $bgcolor);
        $column2               = new Cardwall_Column(100, $label, $bgcolor);
        $columns               = new Cardwall_OnTop_Config_ColumnCollection([$column1, $column2]);
        $cardincell_presenter1 = $this->createMock(Cardwall_CardInCellPresenter::class);
        $cardincell_presenter1->method('getArtifact')->willReturn($artifact1);
        $cardincell_presenter2 = $this->createMock(Cardwall_CardInCellPresenter::class);
        $cardincell_presenter2->method('getArtifact')->willReturn($artifact2);

        $this->config->method('isInColumn')
            ->withConsecutive(
                [$artifact1, self::anything(), $column1],
                [$artifact2, self::anything(), $column1],
                [$artifact1, self::anything(), $column2],
                [$artifact2, self::anything(), $column2],
            )
            ->willReturnOnConsecutiveCalls(true, false, false, true);

        $swimlines = $this->factory->getCells($columns, [$cardincell_presenter1, $cardincell_presenter2]);
        $expected  = [
            ['column_id' => 55, 'column_stacked' => true, 'cardincell_presenters' => [$cardincell_presenter1]],
            ['column_id' => 100, 'column_stacked' => true, 'cardincell_presenters' => [$cardincell_presenter2]],
        ];
        self::assertSame($expected, $swimlines);
    }

    public function testItIgnoresPresentersIfThereIsNoMatchingColumn(): void
    {
        $artifact             = ArtifactTestBuilder::anArtifact(1)->build();
        $column               = new Cardwall_Column(55, null, null);
        $columns              = new Cardwall_OnTop_Config_ColumnCollection();
        $columns[]            = $column;
        $cardincell_presenter = $this->createMock(Cardwall_CardInCellPresenter::class);
        $cardincell_presenter->method('getArtifact')->willReturn($artifact);

        $this->config->method('isInColumn')->with($artifact, self::anything(), $column)->willReturn(false);

        $swimlines = $this->factory->getCells($columns, [$cardincell_presenter]);
        $expected  = [['column_id' => 55, 'column_stacked' => true, 'cardincell_presenters' => []]];
        self::assertSame($expected, $swimlines);
    }
}
