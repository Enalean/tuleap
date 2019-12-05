<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectHistoryDao;
use TroveCat;
use TroveCatFactory;

class ProjectCategoriesUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\MockInterface|ProjectCategoriesUpdater
     */
    private $updater;
    /**
     * @var Mockery\MockInterface|TroveSetNodeFacade
     */
    private $set_node_facade;
    /**
     * @var Mockery\MockInterface|TroveCatFactory
     */
    private $factory;
    /**
     * @var Mockery\MockInterface|ProjectHistoryDao
     */
    private $history_dao;

    /** @before */
    public function instantiateMocks(): void
    {
        $this->project     = Mockery::mock(Project::class);
        $this->factory     = Mockery::mock(TroveCatFactory::class);
        $this->history_dao = Mockery::mock(ProjectHistoryDao::class);
        $this->set_node_facade = Mockery::mock(TroveSetNodeFacade::class);

        $this->project->shouldReceive('getID')->andReturn(42);

        $this->factory->shouldReceive('getTopCategoriesWithNbMaxCategories')->andReturn(
            [
                ['trove_cat_id' => 1, 'nb_max_values' => 3],
                ['trove_cat_id' => 2, 'nb_max_values' => 1],
                ['trove_cat_id' => 4, 'nb_max_values' => 2],
            ]
        );
        $this->factory->shouldReceive('getTree')->andReturn([
            1 => $this->getTrove('1')
                ->addChildren($this->getTrove('11'))
                ->addChildren($this->getTrove('12')),
            2 => $this->getTrove('2')
                ->addChildren($this->getTrove('21')),
            4 => $this->getTrove('4')
                ->addChildren(
                    $this->getTrove('41')
                        ->addChildren($this->getTrove('411'))
                )
        ]);

        $this->factory->shouldReceive('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->andReturn([])->byDefault();

        $this->updater = new ProjectCategoriesUpdater($this->factory, $this->history_dao, $this->set_node_facade);
    }

    private function getTrove($id): TroveCat
    {
        return new TroveCat($id, '', '');
    }

    public function testAddEntryInProjectHistory(): void
    {
        $this->history_dao
            ->shouldReceive('groupAddHistory')
            ->once()
            ->with('changed_trove', "", 42);

        $this->factory->shouldReceive('removeProjectTopCategoryValue');
        $this->set_node_facade->shouldReceive('setNode');

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => ['', '11']]));
    }

    public function testItUpdatesCategoriesValues(): void
    {
        $this->history_dao->shouldReceive('groupAddHistory');

        $this->factory->shouldReceive('removeProjectTopCategoryValue')->with($this->project, 2)->once();
        $this->factory->shouldReceive('removeProjectTopCategoryValue')->with($this->project, 1)->once();
        $this->set_node_facade->shouldReceive('setNode')->with($this->project, 11, 1)->once();
        $this->set_node_facade->shouldReceive('setNode')->with($this->project, 12, 1)->once();
        $this->set_node_facade->shouldReceive('setNode')->with($this->project, 21, 2)->once();

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => ['', '11', '12'], 2 => ['', '21']]));
    }

    public function testItRespectsNbMaxValues(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->factory->shouldNotReceive('setNode');

        $this->expectException(NbMaxValuesException::class);

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => ['', '11', '12', '13', '14'], 2 => ['', '21', '22']]));
    }

    public function testItIgnoresSubmittedCategoryIfItIsNotInTopLevelOnes(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->factory->shouldNotReceive('setNode');

        $this->expectException(NotRootCategoryException::class);

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([3 => ['', '31']]));
    }

    public function testItIgnoresSubmittedCategoryIfValueIsNotAnArray(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->set_node_facade->shouldNotReceive('setNode');

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => '23']));
    }

    public function testItEnsuresThatMandatoryCategoriesAreSet(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->set_node_facade->shouldNotReceive('setNode');

        $this->factory->shouldReceive('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->andReturn([
           new TroveCat(1, '', ''),
           new TroveCat(2, '', ''),
        ]);

        $this->expectException(MissingMandatoryCategoriesException::class);

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([1 => ['', '11', '12'], 2 => ['']]));
    }

    public function testCheckEnsuresThatMandatoryCategoriesAreSet(): void
    {
        $this->factory->shouldReceive('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->andReturn([
            new TroveCat(1, '', ''),
            new TroveCat(2, '', ''),
        ]);

        $this->expectException(MissingMandatoryCategoriesException::class);

        $this->updater->checkCollectionConsistency(CategoryCollection::buildFromWebPayload([1 => ['', '11', '12'], 2 => ['']]));
    }

    public function testCheckEnsuresThatSubmittedCategoryBelongsToTheHierarchy(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->set_node_facade->shouldNotReceive('setNode');

        $this->expectException(InvalidValueForRootCategoryException::class);

        $this->updater->checkCollectionConsistency(CategoryCollection::buildFromWebPayload([1 => ['', '21']]));
    }

    public function testCheckEnsuresThatSubmittedCategoryBelongsToTheHierarchyRecursively(): void
    {
        $this->history_dao->shouldReceive('groupAddHistory');

        $this->factory->shouldReceive('removeProjectTopCategoryValue')->with($this->project, 4)->once();
        $this->set_node_facade->shouldReceive('setNode')->with($this->project, 411, 4)->once();

        $this->updater->update($this->project, CategoryCollection::buildFromWebPayload([4 => ['', '411']]));
    }

    public function testCheckEnsuresThatAllSubmittedCategoryBelongsToTheHierarchy(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->set_node_facade->shouldNotReceive('setNode');

        $this->expectException(InvalidValueForRootCategoryException::class);

        $this->updater->checkCollectionConsistency(CategoryCollection::buildFromWebPayload([4 => ['', '411', '21']]));
    }

    public function testCheckEnsuresThanSubmittedValueIdIsDifferentThanCategoryId(): void
    {
        $this->history_dao->shouldNotReceive('groupAddHistory');
        $this->factory->shouldNotReceive('removeProjectTopCategoryValue');
        $this->set_node_facade->shouldNotReceive('setNode');

        $this->expectException(InvalidValueForRootCategoryException::class);

        $this->updater->checkCollectionConsistency(CategoryCollection::buildFromWebPayload([4 => [4]]));
    }
}
