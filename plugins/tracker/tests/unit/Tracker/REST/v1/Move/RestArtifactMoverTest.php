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

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Action\MoveArtifact;
use Tuleap\Tracker\Action\MoveArtifactByDuckTyping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\AddPostMoveArtifactFeedbackStub;
use Tuleap\Tracker\Test\Stub\CollectDryRunTypingFieldStub;
use Tuleap\Tracker\Test\Stub\FeedbackFieldCollectorInterfaceStub;
use Tuleap\Tracker\Test\Stub\MoveArtifactByDuckTypingStub;
use Tuleap\Tracker\Test\Stub\MoveArtifactStub;

final class RestArtifactMoverTest extends TestCase
{
    use ForgeConfigSandbox;

    private AddPostMoveArtifactFeedback $post_move_action;
    private MoveArtifactByDuckTyping $mega_mover;
    private MoveRestArtifact $artifact_move;
    private FeedbackFieldCollectorInterface $feedback_collector;
    private MoveArtifact $move_action;

    protected function setUp(): void
    {
        $this->move_action        = MoveArtifactStub::andReturnRemainingDeletions();
        $this->post_move_action   = AddPostMoveArtifactFeedbackStub::build();
        $this->mega_mover         = MoveArtifactByDuckTypingStub::withReturnRandomLimit();
        $this->feedback_collector = FeedbackFieldCollectorInterfaceStub::withFields([], [], []);
        $dry_run_collector        =
            CollectDryRunTypingFieldStub::withCollectionOfField(DuckTypedMoveFieldCollection::fromFields([], [], [], []));

        $this->artifact_move = new RestArtifactMover(
            $this->move_action,
            $this->post_move_action,
            $this->mega_mover,
            $this->feedback_collector,
            $dry_run_collector
        );
    }

    public function testItMoveArtifactBasedOnSemanticsModeWithoutFeedback(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(1)->inTracker($source_tracker)->build();
        $user           = UserTestBuilder::anActiveUser()->build();
        $this->artifact_move->move($source_tracker, $target_tracker, $artifact, $user, false);

        self::assertSame(1, $this->move_action->getCallCount());
        self::assertSame(0, $this->post_move_action->getCallCount());
        self::assertSame(0, $this->mega_mover->getCallCount());
    }

    public function testItMoveArtifactBasedOnSemanticsModeWithFeedback(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(1)->inTracker($source_tracker)->build();
        $user           = UserTestBuilder::anActiveUser()->build();
        $this->artifact_move->move($source_tracker, $target_tracker, $artifact, $user, true);

        self::assertSame(1, $this->move_action->getCallCount());
        self::assertSame(1, $this->post_move_action->getCallCount());
        self::assertSame(0, $this->mega_mover->getCallCount());
    }

    public function testItMovesArtifactBasedOnDuckTypingModeWithoutFeedback(): void
    {
        \ForgeConfig::set("feature_flag_enable_complete_move_artifact", "1");

        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(1)->inTracker($source_tracker)->build();
        $user           = UserTestBuilder::anActiveUser()->build();
        $this->artifact_move->move($source_tracker, $target_tracker, $artifact, $user, false);

        self::assertSame(0, $this->move_action->getCallCount());
        self::assertSame(0, $this->post_move_action->getCallCount());
        self::assertSame(1, $this->mega_mover->getCallCount());
    }

    public function testItMovesArtifactBasedOnDuckTypingModeWithFeedback(): void
    {
        \ForgeConfig::set("feature_flag_enable_complete_move_artifact", "1");

        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(1)->inTracker($source_tracker)->build();
        $user           = UserTestBuilder::anActiveUser()->build();
        $this->artifact_move->move($source_tracker, $target_tracker, $artifact, $user, true);

        self::assertSame(0, $this->move_action->getCallCount());
        self::assertSame(1, $this->post_move_action->getCallCount());
        self::assertSame(1, $this->mega_mover->getCallCount());
    }
}
