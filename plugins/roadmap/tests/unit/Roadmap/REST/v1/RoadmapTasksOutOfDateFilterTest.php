<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use DateTimeImmutable;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RoadmapTasksOutOfDateFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItRemovesArtifactsThatAreOutOfDate(): void
    {
        $task_1 = ArtifactTestBuilder::anArtifact(1)->build();
        $task_2 = ArtifactTestBuilder::anArtifact(2)->build();
        $task_3 = ArtifactTestBuilder::anArtifact(3)->build();

        $filter = new RoadmapTasksOutOfDateFilter(
            new class implements IDetectIfArtifactIsOutOfDate {
                public function isArtifactOutOfDate(
                    Artifact $artifact,
                    DateTimeImmutable $now,
                    \PFUser $user,
                    TrackersWithUnreadableStatusCollection $trackers_with_unreadable_status_collection,
                ): bool {
                    return $artifact->getId() === 2;
                }
            }
        );

        self::assertEquals(
            [$task_1, $task_3],
            array_values(
                $filter->filterOutOfDateArtifacts(
                    [$task_1, $task_2, $task_3],
                    new DateTimeImmutable(),
                    UserTestBuilder::aUser()->build(),
                    new TrackersWithUnreadableStatusCollection(new NullLogger()),
                )
            )
        );
    }
}
