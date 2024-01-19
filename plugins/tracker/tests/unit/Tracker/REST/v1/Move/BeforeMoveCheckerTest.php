<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Move;

use Luracast\Restler\RestException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedChecker;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedDAO;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\Artifact\Artifact;

final class BeforeMoveCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private BeforeMoveChecker $before_move_checker;
    private ProjectStatusVerificator&\PHPUnit\Framework\MockObject\Stub $project_status_verificator;
    private \EventManager&\PHPUnit\Framework\MockObject\Stub $event_manager;
    private \Project $project;
    private \PFUser $user;
    private MoveActionAllowedDAO&\PHPUnit\Framework\MockObject\MockObject $move_dao;

    protected function setUp(): void
    {
        $this->project_status_verificator = $this->createStub(ProjectStatusVerificator::class);
        $this->event_manager              = $this->createStub(\EventManager::class);
        $this->move_dao                   = $this->createMock(MoveActionAllowedDAO::class);

        $this->before_move_checker = new BeforeMoveChecker(
            $this->event_manager,
            $this->project_status_verificator,
            new MoveActionAllowedChecker($this->move_dao),
        );

        $this->project = ProjectTestBuilder::aProject()->build();
        $this->user    = UserTestBuilder::aUser()->build();

        $this->project_status_verificator->expects(self::atLeast(1))
            ->method("checkProjectStatusAllowsAllUsersToAccessIt")->with($this->project);
    }

    public function testItDoesNotThrowIfEverythingIsOK(): void
    {
        $source_tracker = $this->getTrackerUserIsAdmin(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $source_tracker->method("getId")->willReturn(1);
        $target_tracker = $this->getTrackerUserIsAdmin(888);
        $target_tracker->method('isDeleted')->willReturn(false);
        $target_tracker->method("getId")->willReturn(2);
        $this->event_manager->method('processEvent');
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(true);

        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function testIThrowsWhenProjectIsDeleted(): void
    {
        $source_tracker = $this->getTrackerUserIsAdmin(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event          = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $target_tracker = $this->getTrackerUserIsAdmin(888);
        $target_tracker->method('isDeleted')->willReturn(true);
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function testIThrowsWhenTrackerForbidMoveAction(): void
    {
        $source_tracker = $this->getTrackerUserIsAdmin(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event          = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $target_tracker = $this->getTrackerUserIsAdmin(888);
        $target_tracker->method('isDeleted')->willReturn(false);
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function testIThrowsWhenUserIsNotAdminOfSourceProject(): void
    {
        $source_tracker = $this->getTrackerUserIsMereMortal(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event          = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $target_tracker = $this->getTrackerUserIsAdmin(888);
        $target_tracker->method('isDeleted')->willReturn(false);
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function testIThrowsWhenUserIsNotAdminOfDestinationProject(): void
    {
        $source_tracker = $this->getTrackerUserIsAdmin(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event          = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $target_tracker = $this->getTrackerUserIsMereMortal(888);
        $target_tracker->method('isDeleted')->willReturn(false);
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function testIThrowsWhenMoveIsITheSameTracker(): void
    {
        $source_tracker = $this->getTrackerUserIsAdmin(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $source_tracker->method("getId")->willReturn(1);
        $target_tracker = $this->getTrackerUserIsAdmin(999);
        $target_tracker->method('isDeleted')->willReturn(false);
        $target_tracker->method("getId")->willReturn(1);
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function testIThrowsWhenAnExternalPluginForbidTheMove(): void
    {
        \ForgeConfig::set('feature_flag_rollback_to_semantic_move_artifact', "1");

        $source_tracker = $this->getTrackerUserIsAdmin(999);
        $artifact       = $this->createStub(Artifact::class);
        $artifact->method('getTracker')->willReturn($source_tracker);
        $event = new MoveArtifactActionAllowedByPluginRetriever($artifact, $this->user);
        $source_tracker->method("getId")->willReturn(1);
        $target_tracker = $this->getTrackerUserIsAdmin(888);
        $target_tracker->method('isDeleted')->willReturn(false);
        $target_tracker->method("getId")->willReturn(2);
        $this->event_manager->method('processEvent');
        $this->move_dao->method("isMoveActionAllowedInTracker")->willReturn(true);

        $event->setCanNotBeMoveDueToExternalPlugin('You shall not pass');
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->before_move_checker->check($source_tracker, $target_tracker, $this->user, $artifact, $event);
    }

    public function getTrackerUserIsAdmin(int $tracker_id): \Tracker&\PHPUnit\Framework\MockObject\Stub
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method("getProject")->willReturn($this->project);
        $tracker->method("userIsAdmin")->willReturn(true);
        $tracker->method("getId")->willReturn($tracker_id);
        $tracker->method("getName")->willReturn('tracker01');

        return $tracker;
    }

    public function getTrackerUserIsMereMortal(int $tracker_id): \Tracker&\PHPUnit\Framework\MockObject\Stub
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method("getProject")->willReturn($this->project);
        $tracker->method("userIsAdmin")->willReturn(false);
        $tracker->method("getId")->willReturn($tracker_id);

        return $tracker;
    }
}
