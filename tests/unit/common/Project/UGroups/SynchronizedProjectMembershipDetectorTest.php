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

namespace Tuleap\Project\UGroups;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class SynchronizedProjectMembershipDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SynchronizedProjectMembershipDetector $detector;
    private SynchronizedProjectMembershipDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->dao      = $this->createMock(SynchronizedProjectMembershipDao::class);
        $this->detector = new SynchronizedProjectMembershipDetector($this->dao);
    }

    public function testItReturnsTrueWhenTheProjectIsPrivate(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withAccessPrivate()
            ->build();

        self::assertTrue($this->detector->isSynchronizedWithProjectMembers($project));
    }

    public function testItReturnsTrueWhenTheProjectIsPublicAndHasSynchronizedManagementEnabled(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(165)
            ->withAccessPublic()
            ->build();
        $this->dao
            ->expects(self::once())
            ->method('isEnabled')
            ->with(165)
            ->willReturn(true);

        self::assertTrue($this->detector->isSynchronizedWithProjectMembers($project));
    }

    public function testItReturnsFalseWhenTheProjectIsPublicAndHasSynchronizedManagementDisabled(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(165)
            ->withAccessPublic()
            ->build();
        $this->dao
            ->expects(self::once())
            ->method('isEnabled')
            ->with(165)
            ->willReturn(false);

        self::assertFalse($this->detector->isSynchronizedWithProjectMembers($project));
    }
}
