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

use DateTimeImmutable;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\I18NRestException;

class BaselineControllerIntTest extends IntegrationTestCaseWithStubs
{
    use GlobalLanguageMock;

    /** @var BaselineController */
    private $controller;

    /** @before */
    public function getTestedComponent(): void
    {
        $this->controller = $this->getContainer()->get(BaselineController::class);
    }

    public function testPost(): void
    {
        $artifact = BaselineArtifactFactory::one()->id(2)->build();
        $this->baseline_artifact_repository->addAt($artifact, $this->clock->now());

        $this->controller->post('My first baseline', 2, null);

        self::assertEquals(1, $this->baseline_repository->count());
        $baseline = $this->baseline_repository->findAny();
        self::assertNotNull($baseline);
        self::assertEquals('My first baseline', $baseline->getName());
        self::assertEquals($artifact, $baseline->getArtifact());
        self::assertEquals($this->current_user_provider->getUser()->getId(), $baseline->getAuthor()->getId());
        self::assertEquals($this->clock->now(), $baseline->getSnapshotDate());
    }

    public function testPostWithoutSnapshotDate(): void
    {
        $artifact = BaselineArtifactFactory::one()->id(2)->build();
        $this->baseline_artifact_repository->addAt($artifact, $this->clock->now());

        $this->controller->post('My first baseline', 2, null);

        $baseline = $this->baseline_repository->findAny();
        self::assertNotNull($baseline);
        self::assertEquals($this->clock->now(), $baseline->getSnapshotDate());
    }

    public function testPostWithSnapshotDate(): void
    {
        $artifact = BaselineArtifactFactory::one()->id(2)->build();
        $this->baseline_artifact_repository->addAt($artifact, $this->clock->now());

        $this->controller->post('My first baseline', 2, '2019-03-21T11:47:04+02:00');

        $baseline               = $this->baseline_repository->findAny();
        $expected_snapshot_date = DateTimeImmutable::createFromFormat(
            BaselineController::DATE_TIME_FORMAT,
            '2019-03-21T11:47:04+02:00'
        );
        self::assertNotNull($baseline);
        self::assertEquals($expected_snapshot_date, $baseline->getSnapshotDate());
    }

    public function testDelete(): void
    {
        $baseline = BaselineFactory::one()->id(2)->build();
        $this->baseline_repository->addBaseline($baseline);
        $this->controller->delete(2);
        self::assertNotContains(2, array_keys($this->baseline_repository->findAllById()));
    }

    public function testDeleteThrowsWhenAssociatedToAComparison(): void
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(409);

        $baseline = BaselineFactory::one()->id(2)->build();
        $this->baseline_repository->addBaseline($baseline);

        $comparison = TransientComparisonFactory::one()->base($baseline)->build();
        $this->comparison_repository->add($comparison, $this->current_user);

        $this->controller->delete(2);
    }
}
