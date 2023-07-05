<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\GlobalLanguageMock;

class BaselineArtifactControllerIntTest extends IntegrationTestCaseWithStubs
{
    use GlobalLanguageMock;

    private const EPIC_ID    = 737;
    private const EPIC_TITLE = 'Epic #737';

    /** @var BaselineArtifactController */
    private $controller;

    /** @before */
    public function getTestedComponent(): void
    {
        $this->controller = $this->getContainer()->get(BaselineArtifactController::class);
    }

    public function testGetWithoutQuery(): void
    {
        $snapshot_date = DateTimeFactory::one();
        $epic          = BaselineArtifactFactory::one()
            ->id(self::EPIC_ID)
            ->title(self::EPIC_TITLE)
            ->description('Epic description')
            ->status('On going')
            ->trackerName('Epic')
            ->linkedArtifactIds([1, 2, 3])
            ->build();
        $this->baseline_artifact_repository->addAt($epic, $snapshot_date);

        $milestone = BaselineArtifactFactory::one()
            ->linkedArtifactIds([self::EPIC_ID])
            ->build();
        $this->baseline_artifact_repository->addAt($milestone, $snapshot_date);

        $baseline = BaselineFactory::one()
            ->id(1)
            ->snapshotDate($snapshot_date)
            ->artifact($milestone)
            ->build();
        $this->baseline_repository->addBaseline($baseline);

        $artifacts_representation = $this->controller->get(1, null);

        $artifacts_representations = $artifacts_representation->artifacts;
        self::assertEquals(1, count($artifacts_representations));
        $artifact_representation = $artifacts_representations[0];
        self::assertEquals(self::EPIC_ID, $artifact_representation->id);
        self::assertEquals(self::EPIC_TITLE, $artifact_representation->title);
        self::assertEquals('Epic description', $artifact_representation->description);
        self::assertEquals('On going', $artifact_representation->status);
        self::assertEquals('Epic', $artifact_representation->tracker_name);
        self::assertEquals([1, 2, 3], $artifact_representation->linked_artifact_ids);
    }

    public function testGetWithQuery(): void
    {
        $snapshot_date = DateTimeFactory::one();

        $this->baseline_artifact_repository->addAt(
            BaselineArtifactFactory::one()->id(2)->build(),
            $snapshot_date
        );
        $this->baseline_artifact_repository->addAt(
            BaselineArtifactFactory::one()->id(3)->build(),
            $snapshot_date
        );
        $this->baseline_artifact_repository->addAt(
            BaselineArtifactFactory::one()->id(4)->build(),
            $snapshot_date
        );

        $epic = BaselineArtifactFactory::one()
            ->id(9)
            ->linkedArtifactIds([2, 3, 4])
            ->build();
        $this->baseline_artifact_repository->addAt($epic, $snapshot_date);

        $milestone = BaselineArtifactFactory::one()
            ->linkedArtifactIds([9])
            ->build();
        $this->baseline_artifact_repository->addAt($milestone, $snapshot_date);

        $baseline = BaselineFactory::one()
            ->id(1)
            ->snapshotDate($snapshot_date)
            ->artifact($milestone)
            ->build();
        $this->baseline_repository->addBaseline($baseline);

        $query                    = '{"ids": [2,3,4]}';
        $artifacts_representation = $this->controller->get(1, $query);

        $artifact_ids = array_map(
            function (BaselineArtifactRepresentation $artifact_representation) {
                return $artifact_representation->id;
            },
            $artifacts_representation->artifacts
        );
        self::assertEquals([2, 3, 4], $artifact_ids);
    }
}
