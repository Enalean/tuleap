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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/IntegrationTestCaseWithStubs.php';

use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\DateTimeFactory;

class ComparisonControllerIntTest extends IntegrationTestCaseWithStubs
{
    /** @var ComparisonController */
    private $controller;

    /** @before */
    public function getTestedComponent(): void
    {
        $this->controller = $this->getContainer()->get(ComparisonController::class);
    }

    public function testPost(): void
    {
        $base_baseline        = $this->baseline_repository->add(
            BaselineFactory::one()->build(),
            $this->current_user,
            DateTimeFactory::one()
        );
        $compared_to_baseline = $this->baseline_repository->add(
            BaselineFactory::one()
                ->artifact($base_baseline->getArtifact())
                ->build(),
            $this->current_user,
            DateTimeFactory::one()
        );

        $representation = $this->controller->post(
            'My first comparison',
            'Some comment here',
            $base_baseline->getId(),
            $compared_to_baseline->getId()
        );

        self::assertEquals(1, $this->comparison_repository->count());

        $comparison = $this->comparison_repository->findAny();
        self::assertNotNull($comparison);
        self::assertEquals('My first comparison', $comparison->getName());
        self::assertEquals('Some comment here', $comparison->getComment());
        self::assertEquals($base_baseline->getId(), $comparison->getBaseBaseline()->getId());
        self::assertEquals($compared_to_baseline->getId(), $comparison->getComparedToBaseline()->getId());

        self::assertNotNull($representation->id);
        self::assertEquals('My first comparison', $representation->name);
        self::assertEquals('Some comment here', $representation->comment);
        self::assertEquals($base_baseline->getId(), $representation->base_baseline_id);
        self::assertEquals($compared_to_baseline->getId(), $representation->compared_to_baseline_id);
    }

    public function testPostThrows404WhenBaseBaselineDoesNotExist(): void
    {
        $this->expectException(NotFoundRestException::class);

        $compared_to_baseline = $this->baseline_repository->add(
            BaselineFactory::one()->build(),
            $this->current_user,
            DateTimeFactory::one()
        );

        $this->controller->post(
            'My first comparison',
            'Some comment here',
            999,
            $compared_to_baseline->getId()
        );
    }

    public function testPostThrows404WhenComparedToBaselineDoesNotExist(): void
    {
        $this->expectException(NotFoundRestException::class);

        $based_baseline = $this->baseline_repository->add(
            BaselineFactory::one()->build(),
            $this->current_user,
            DateTimeFactory::one()
        );

        $this->controller->post(
            'My first comparison',
            'Some comment here',
            $based_baseline->getId(),
            999
        );
    }

    public function testGet(): void
    {
        $comparison = $this->comparison_repository->add(
            TransientComparisonFactory::one()
                ->name('My first comparison')
                ->comment(null)
                ->base(BaselineFactory::one()->id(1)->build())
                ->comparedTo(BaselineFactory::one()->id(2)->build())
                ->build(),
            $this->current_user
        );

        $representation = $this->controller->getById($comparison->getId());

        self::assertEquals($comparison->getId(), $representation->id);
        self::assertEquals('My first comparison', $representation->name);
        self::assertNull($representation->comment);
        self::assertEquals(1, $representation->base_baseline_id);
        self::assertEquals(2, $representation->compared_to_baseline_id);
    }

    public function testDelete(): void
    {
        $comparison = $this->comparison_repository->add(
            TransientComparisonFactory::one()->build(),
            $this->current_user
        );

        $comparison_id = $comparison->getId();
        $this->controller->delete($comparison_id);

        self::assertNotContains($comparison_id, array_keys($this->comparison_repository->findAllById()));
    }
}
