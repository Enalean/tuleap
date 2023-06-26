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

namespace Tuleap\Taskboard\Routing;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Project;
use Tuleap\Request\NotFoundException;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class MilestoneExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Planning_MilestoneFactory&MockObject $factory;
    private MilestoneExtractor $extractor;
    private PFUser $user;
    private MilestoneIsAllowedChecker&MockObject $checker;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::aUser()->build();

        $this->factory = $this->createMock(Planning_MilestoneFactory::class);
        $this->checker = $this->createMock(MilestoneIsAllowedChecker::class);

        $this->extractor = new MilestoneExtractor($this->factory, $this->checker);
    }

    public function testNotFoundExceptionWhenMilestoneDoesNotExist(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testNotFoundExceptionWhenProjectMilestoneIsNotTheOneGivenInArgument(): void
    {
        $project = $this->buildAnotherProject();

        $milestone = $this->buildMockMilestone($project);

        $this->factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->willReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testNotFoundExceptionWhenMilestoneIsNotAllowed(): void
    {
        $project = $this->buildProject();

        $milestone = $this->buildMockMilestone($project);

        $this->factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->willReturn($milestone);

        $this->checker
            ->expects(self::once())
            ->method('checkMilestoneIsAllowed')
            ->with($milestone)
            ->willThrowException(new MilestoneIsNotAllowedException());

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testItReturnsTheMilestone(): void
    {
        $project = $this->buildProject();

        $milestone = $this->buildMockMilestone($project);

        $this->factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->willReturn($milestone);

        $this->checker
            ->expects(self::once())
            ->method('checkMilestoneIsAllowed')
            ->with($milestone);

        self::assertEquals(
            $milestone,
            $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project'])
        );
    }

    private function buildProject(): Project
    {
        return ProjectTestBuilder::aProject()
            ->withId(42)
            ->withUnixName('my-project')
            ->withPublicName('My project')
            ->build();
    }

    private function buildAnotherProject(): Project
    {
        return ProjectTestBuilder::aProject()
            ->withId(43)
            ->withUnixName('another-project')
            ->withPublicName('Another project')
            ->build();
    }

    private function buildMockMilestone(Project $project): \PHPUnit\Framework\MockObject\MockObject&Planning_Milestone
    {
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getTrackerId')->willReturn(101);
        $milestone->method('getProject')->willReturn($project);

        return $milestone;
    }
}
