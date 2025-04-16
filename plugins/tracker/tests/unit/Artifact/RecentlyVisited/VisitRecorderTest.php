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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VisitRecorderTest extends TestCase
{
    private RecentlyVisitedDao&MockObject $dao;
    private VisitRecorder $visit_recorder;

    public function setUp(): void
    {
        $this->dao            = $this->createMock(RecentlyVisitedDao::class);
        $this->visit_recorder = new VisitRecorder($this->dao);
    }

    public function testVisitOfAnAuthenticatedUserIsSaved(): void
    {
        $this->dao->expects($this->once())->method('save');

        $user     = UserTestBuilder::anActiveUser()->withId(102)->build();
        $artifact = ArtifactTestBuilder::anArtifact(1003)->build();

        $this->visit_recorder->record($user, $artifact);
    }

    public function testVisitOfAnAnonymousUserIsNotSaved(): void
    {
        $this->dao->expects($this->never())->method('save');

        $user = UserTestBuilder::anAnonymousUser()->build();

        $this->visit_recorder->record($user, ArtifactTestBuilder::anArtifact(45)->build());
    }
}
