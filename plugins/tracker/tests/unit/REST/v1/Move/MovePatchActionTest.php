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
use Project;
use Psr\Log\NullLogger;
use Tracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\v1\ArtifactPatchRepresentation;
use Tuleap\Tracker\REST\v1\MoveRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CheckBeforeMoveStub;
use Tuleap\Tracker\Test\Stub\MoveDryRunStub;
use Tuleap\Tracker\Test\Stub\MoveRestArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

final class MovePatchActionTest extends TestCase
{
    private Project $project;
    private Tracker $tracker;
    private Artifact $artifact;
    private CheckBeforeMove $before_move_checker;
    private MoveDryRun $dry_run_move;
    private ArtifactPatchRepresentation $patch_representation;
    private \PFUser $user;

    protected function setUp(): void
    {
        $tracker_id     = 1234;
        $this->project  = ProjectTestBuilder::aProject()->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject($this->project)->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->user = UserTestBuilder::anActiveUser()->build();

        $this->dry_run_move        = MoveDryRunStub::build();
        $this->before_move_checker = CheckBeforeMoveStub::build();

        $this->patch_representation                   = new ArtifactPatchRepresentation();
        $this->patch_representation->move             = new MoveRepresentation();
        $this->patch_representation->move->dry_run    = true;
        $this->patch_representation->move->tracker_id = $tracker_id;
    }

    public function testItThrowsWhenSourceTrackerIsNotFound(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withoutTracker();
        $move_rest_artifact = MoveRestArtifactStub::andReturnRemainingDeletions();

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());
    }

    public function testRethrowsMoveArtifactNotDoneException(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withTracker($this->tracker);
        $move_rest_artifact = MoveRestArtifactStub::andThrowMoveArtifactNotDone();

        $this->patch_representation->move->dry_run = false;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());
    }

    public function testRethrowsMoveSemanticException(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withTracker($this->tracker);
        $move_rest_artifact = MoveRestArtifactStub::andThrowMoveArtifactSemanticsException();

        $this->patch_representation->move->dry_run = false;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());
    }

    public function testRethrowsTargetProjectIsNotActiveException(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withTracker($this->tracker);
        $move_rest_artifact = MoveRestArtifactStub::andMoveArtifactTargetProjectNotActiveException();

        $this->patch_representation->move->dry_run = false;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());
    }

    public function testItThrowsWhenThereAreNoFieldsToMove(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withTracker($this->tracker);
        $move_rest_artifact = MoveRestArtifactStub::andMoveArtifactNoValuesToProcessException();

        $this->patch_representation->move->dry_run = false;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());
    }

    public function testItReturnsARepresentationWitDryRunWhenMoveIsComplete(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withTracker($this->tracker);
        $move_rest_artifact = MoveRestArtifactStub::andReturnRemainingDeletions();

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());

        $this->assertSame(1, $this->dry_run_move->getCallCount());
        $this->assertSame(0, $move_rest_artifact->getCallCount());
    }

    public function testItReturnsARepresentationWithoutDryRunWhenMoveIsComplete(): void
    {
        $retrieve_tracker   = RetrieveTrackerStub::withTracker($this->tracker);
        $move_rest_artifact = MoveRestArtifactStub::andReturnRemainingDeletions();

        $this->patch_representation->move->dry_run = false;

        $move_patch_action = new MovePatchAction(
            $retrieve_tracker,
            $this->dry_run_move,
            $move_rest_artifact,
            $this->before_move_checker,
        );
        $move_patch_action->patchMove($this->patch_representation, $this->user, $this->artifact, new NullLogger());

        $this->assertSame(0, $this->dry_run_move->getCallCount());
        $this->assertSame(1, $move_rest_artifact->getCallCount());
    }
}
