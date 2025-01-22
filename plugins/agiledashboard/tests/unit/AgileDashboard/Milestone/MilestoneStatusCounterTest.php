<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Dao\ArtifactDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class MilestoneStatusCounterTest extends TestCase
{
    private AgileDashboard_Milestone_MilestoneStatusCounter $counter;
    private PFUser $user;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private ArtifactDao&MockObject $artifact_dao;
    private BacklogItemDao&MockObject $backlog_dao;

    protected function setUp(): void
    {
        $this->backlog_dao      = $this->createMock(BacklogItemDao::class);
        $this->artifact_dao     = $this->createMock(ArtifactDao::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->user             = UserTestBuilder::buildWithDefaults();
        $this->counter          = new AgileDashboard_Milestone_MilestoneStatusCounter(
            $this->backlog_dao,
            $this->artifact_dao,
            $this->artifact_factory
        );

        $artifact = ArtifactTestBuilder::anArtifact(1)
            ->userCanView($this->user)
            ->build();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
    }

    public function testItDoesntFetchAnythingWhenNoMilestoneId(): void
    {
        $this->backlog_dao->expects(self::never())->method('getBacklogArtifacts');
        $result = $this->counter->getStatus($this->user, null);
        $this->assertEquals([
            Artifact::STATUS_OPEN   => 0,
            Artifact::STATUS_CLOSED => 0,
        ], $result);
    }

    public function testItReturnsZeroOpenClosedWhenNoArtifacts(): void
    {
        $this->backlog_dao->method('getBacklogArtifacts')->with(12)->willReturn([]);
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals([
            Artifact::STATUS_OPEN   => 0,
            Artifact::STATUS_CLOSED => 0,
        ], $result);
    }

    public function testItDoesntTryToFetchChildrenWhenNoBacklog(): void
    {
        $this->backlog_dao->method('getBacklogArtifacts')->willReturn([]);
        $this->artifact_dao->expects(self::never())->method('getChildrenForArtifacts');
        $this->counter->getStatus($this->user, 12);
    }

    public function testItFetchesTheStatusOfReturnedArtifacts(): void
    {
        $this->backlog_dao->method('getBacklogArtifacts')->with(12)->willReturn([['id' => 35], ['id' => 36]]);
        $this->artifact_dao->method('getArtifactsStatusByIds')->with([35, 36])->willReturn([
            ['id' => 36, 'status' => Artifact::STATUS_OPEN],
            ['id' => 35, 'status' => Artifact::STATUS_CLOSED],
        ]);
        $this->artifact_dao->method('getChildrenForArtifacts')->willReturn([]);
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals([
            Artifact::STATUS_OPEN   => 1,
            Artifact::STATUS_CLOSED => 1,
        ], $result);
    }

    public function testItFetchesTheStatusOfReturnedArtifactsAtSublevel(): void
    {
        // Level 0
        $this->backlog_dao->method('getBacklogArtifacts')->with(12)->willReturn([['id' => 35], ['id' => 36]]);

        // Level -1
        $this->artifact_dao->method('getChildrenForArtifacts')->with([35, 36])->willReturn(
            [['id' => 38], ['id' => 39], ['id' => 40]]
        );

        $this->artifact_dao->method('getArtifactsStatusByIds')
            ->withConsecutive(
                [[35, 36]], // Level 0
                [[38, 39, 40]] // Level -1
            )->willReturnOnConsecutiveCalls(
                [
                    ['id' => 36, 'status' => Artifact::STATUS_OPEN],
                    ['id' => 35, 'status' => Artifact::STATUS_CLOSED],
                ],
                [
                    ['id' => 38, 'status' => Artifact::STATUS_OPEN],
                    ['id' => 39, 'status' => Artifact::STATUS_CLOSED],
                    ['id' => 40, 'status' => Artifact::STATUS_CLOSED],
                ],
            );

        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals([
            Artifact::STATUS_OPEN   => 2,
            Artifact::STATUS_CLOSED => 3,
        ], $result);
    }

    public function testItDoesntCountBacklogElementNotReadable(): void
    {
        $artifact       = ArtifactTestBuilder::anArtifact(35)
            ->userCannotView($this->user)
            ->build();
        $other_artifact = ArtifactTestBuilder::anArtifact(36)
            ->userCanView($this->user)
            ->build();
        $this->artifact_factory->method('getArtifactById')
            ->withConsecutive([35], [36])
            ->willReturnOnConsecutiveCalls($artifact, $other_artifact);

        $this->backlog_dao->method('getBacklogArtifacts')->with(12)->willReturn([['id' => 35], ['id' => 36]]);
        $this->artifact_dao->method('getArtifactsStatusByIds')->willReturn([['id' => 36, 'status' => Artifact::STATUS_OPEN]]);
        $this->artifact_dao->method('getChildrenForArtifacts')->willReturn([]);
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals([
            Artifact::STATUS_OPEN   => 1,
            Artifact::STATUS_CLOSED => 0,
        ], $result);
    }

    public function testItDoesntCountSubElementsNotReadable(): void
    {
        $artifact_36 = ArtifactTestBuilder::anArtifact(36)
            ->userCanView($this->user)
            ->build();
        $artifact_37 = ArtifactTestBuilder::anArtifact(37)
            ->userCannotView($this->user)
            ->build();
        $artifact_38 = ArtifactTestBuilder::anArtifact(38)
            ->userCanView($this->user)
            ->build();

        $this->artifact_factory->method('getArtifactById')
            ->withConsecutive([36], [37], [38])
            ->willReturnOnConsecutiveCalls($artifact_36, $artifact_37, $artifact_38);

        $this->backlog_dao->method('getBacklogArtifacts')->with(12)->willReturn([['id' => 36]]);
        $this->artifact_dao->method('getArtifactsStatusByIds')
            ->withConsecutive([[36]], [[37, 38]])
            ->willReturnOnConsecutiveCalls(
                [['id' => 36, 'status' => Artifact::STATUS_OPEN]],
                [['id' => 38, 'status' => Artifact::STATUS_OPEN]],
            );
        $this->artifact_dao->method('getChildrenForArtifacts')->willReturn(
            [['id' => 37], ['id' => 38]]
        );

        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals([
            Artifact::STATUS_OPEN   => 2,
            Artifact::STATUS_CLOSED => 0,
        ], $result);
    }
}
