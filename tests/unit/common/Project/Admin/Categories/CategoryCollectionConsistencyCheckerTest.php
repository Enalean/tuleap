<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use TroveCat;
use TroveCatFactory;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class CategoryCollectionConsistencyCheckerTest extends TestCase
{
    private CategoryCollectionConsistencyChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TroveCatFactory
     */
    private $trove_cat_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trove_cat_factory = $this->createMock(TroveCatFactory::class);

        $this->checker = new CategoryCollectionConsistencyChecker(
            $this->trove_cat_factory
        );

        $this->trove_cat_factory->method('getTopCategoriesWithNbMaxCategories')->willReturn(
            [
                ['trove_cat_id' => 1, 'nb_max_values' => 3],
                ['trove_cat_id' => 2, 'nb_max_values' => 1],
                ['trove_cat_id' => 4, 'nb_max_values' => 2],
            ]
        );

        $this->trove_cat_factory->method('getTree')->willReturn([
            1 => $this->buildTrove('1')
                ->addChildren($this->buildTrove('11'))
                ->addChildren($this->buildTrove('12')),
            2 => $this->buildTrove('2')
                ->addChildren($this->buildTrove('21')),
            4 => $this->buildTrove('4')
                ->addChildren(
                    $this->buildTrove('41')
                        ->addChildren($this->buildTrove('411'))
                ),
        ]);
    }

    private function buildTrove($id): TroveCat
    {
        return new TroveCat($id, '', '');
    }

    public function testItThrowsAnExceptionIfThereIsNoRootCategory(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([
            new TroveCat(1, '', ''),
            new TroveCat(2, '', ''),
        ]);

        $this->expectException(NotRootCategoryException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([3 => ['', '31']])
        );
    }

    public function testItThrowsAnExceptionIfNbMaxCategoryIsNotRespected(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([
            new TroveCat(1, '', ''),
            new TroveCat(2, '', ''),
        ]);

        $this->expectException(NbMaxValuesException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([1 => ['', '11', '12', '13', '14'], 2 => ['', '21', '22']])
        );
    }

    public function testItEnsuresThatMandatoryCategoriesAreSet(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([
            new TroveCat(1, '', ''),
            new TroveCat(2, '', ''),
        ]);

        $this->expectException(MissingMandatoryCategoriesException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([1 => ['', '11', '12'], 2 => ['']])
        );
    }

    public function testCheckEnsuresThatSubmittedCategoryBelongsToTheHierarchyRecursively(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([]);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([4 => ['', '411']])
        );

        //Expect no exception
        $this->addToAssertionCount(1);
    }

    public function testCheckEnsuresThatMandatoryCategoriesAreSet(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([
            new TroveCat(1, '', ''),
            new TroveCat(2, '', ''),
        ]);

        $this->expectException(MissingMandatoryCategoriesException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([1 => ['', '11', '12'], 2 => ['']])
        );
    }

    public function testCheckEnsuresThatSubmittedCategoryBelongsToTheHierarchy(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([]);

        $this->expectException(InvalidValueForRootCategoryException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([1 => ['', '21']])
        );
    }

    public function testCheckEnsuresThatAllSubmittedCategoryBelongsToTheHierarchy(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([]);

        $this->expectException(InvalidValueForRootCategoryException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([4 => ['', '411', '21']])
        );
    }

    public function testCheckEnsuresThanSubmittedValueIdIsDifferentThanCategoryId(): void
    {
        $this->trove_cat_factory->method('getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren')->willReturn([]);

        $this->expectException(InvalidValueForRootCategoryException::class);

        $this->checker->checkCollectionConsistency(
            CategoryCollection::buildFromWebPayload([4 => [4]])
        );
    }
}
