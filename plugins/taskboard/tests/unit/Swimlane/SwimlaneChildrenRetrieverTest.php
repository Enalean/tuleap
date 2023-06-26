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
 */

declare(strict_types=1);

namespace Tuleap\Taskboard\Swimlane;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class SwimlaneChildrenRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SwimlaneChildrenRetriever $retriever;

    protected function setUp(): void
    {
        $this->retriever = new SwimlaneChildrenRetriever();
    }

    public function testGetSwimlaneArtifactIdsReturnsLinkedArtifactIds(): void
    {
        $first_linked_artifact  = ArtifactTestBuilder::anArtifact(123)->build();
        $second_linked_artifact = ArtifactTestBuilder::anArtifact(456)->build();
        $swimlane_artifact      = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $swimlane_artifact->expects(self::once())
            ->method('getLinkedArtifacts')
            ->willReturn([$first_linked_artifact, $second_linked_artifact]);

        $current_user = UserTestBuilder::aUser()->build();
        $result       = $this->retriever->getSwimlaneArtifactIds($swimlane_artifact, $current_user);
        self::assertSame(2, count($result));
        self::assertSame(123, $result[0]);
        self::assertSame(456, $result[1]);
    }
}
