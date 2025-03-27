<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Categories;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectHistoryDao;
use TroveCat;
use TroveCatFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectCategoriesUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Project $project;
    private ProjectCategoriesUpdater $updater;
    private TroveSetNodeFacade&MockObject $set_node_facade;
    private TroveCatFactory&MockObject $factory;
    private ProjectHistoryDao&MockObject $history_dao;

        #[\PHPUnit\Framework\Attributes\Before]
    public function instantiateMocks(): void
    {
        $this->project         = ProjectTestBuilder::aProject()->withId(42)->build();
        $this->factory         = $this->createMock(TroveCatFactory::class);
        $this->history_dao     = $this->createMock(ProjectHistoryDao::class);
        $this->set_node_facade = $this->createMock(TroveSetNodeFacade::class);

        $this->factory->method('getTopCategoriesWithNbMaxCategories')->willReturn(
            [
                ['trove_cat_id' => 1, 'nb_max_values' => 3],
                ['trove_cat_id' => 2, 'nb_max_values' => 1],
                ['trove_cat_id' => 4, 'nb_max_values' => 2],
            ]
        );
        $this->factory->method('getTree')->willReturn([
            1 => $this->getTrove('1')
                ->addChildren($this->getTrove('11'))
                ->addChildren($this->getTrove('12')),
            2 => $this->getTrove('2')
                ->addChildren($this->getTrove('21')),
            4 => $this->getTrove('4')
                ->addChildren(
                    $this->getTrove('41')
                        ->addChildren($this->getTrove('411'))
                ),
        ]);

        $this->factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([]);

        $this->updater = new ProjectCategoriesUpdater(
            $this->factory,
            $this->history_dao,
            $this->set_node_facade
        );
    }

    private function getTrove($id): TroveCat
    {
        return new TroveCat($id, '', '');
    }

    public function testAddEntryInProjectHistory(): void
    {
        $this->history_dao
            ->expects($this->once())
            ->method('groupAddHistory')
            ->with('changed_trove', '', 42);

        $this->factory->method('removeProjectTopCategoryValue');
        $this->set_node_facade->method('setNode');

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => ['', '11']]));
    }

    public function testItUpdatesCategoriesValues(): void
    {
        $this->history_dao->method('groupAddHistory');
        $matcher = self::exactly(2);

        $this->factory
            ->expects($matcher)
            ->method('removeProjectTopCategoryValue')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->project, $parameters[0]);
                    self::assertSame(1, $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->project, $parameters[0]);
                    self::assertSame(2, $parameters[1]);
                }
            });
        $matcher = self::exactly(3);
        $this->set_node_facade
            ->expects($matcher)
            ->method('setNode')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->project, $parameters[0]);
                    self::assertSame(11, $parameters[1]);
                    self::assertSame(1, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->project, $parameters[0]);
                    self::assertSame(12, $parameters[1]);
                    self::assertSame(1, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame($this->project, $parameters[0]);
                    self::assertSame(21, $parameters[1]);
                    self::assertSame(2, $parameters[2]);
                }
            });

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => ['', '11', '12'], 2 => ['', '21']]));
    }

    public function testItIgnoresSubmittedCategoryIfValueIsNotAnArray(): void
    {
        $this->history_dao->expects(self::never())->method('groupAddHistory');
        $this->factory->expects(self::never())->method('removeProjectTopCategoryValue');
        $this->set_node_facade->expects(self::never())->method('setNode');

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => '23']));
    }
}
