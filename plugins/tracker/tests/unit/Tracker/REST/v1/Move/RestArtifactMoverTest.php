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
use Tuleap\Tracker\Action\CollectDryRunTypingField;
use Tuleap\Tracker\Action\DuckTypedMoveArtifactLinksMappingBuilder;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\FieldMapping;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Action\MoveArtifact;
use Tuleap\Tracker\Action\MoveArtifactByDuckTyping;
use Tuleap\Tracker\Exception\MoveArtifactNoValuesToProcessException;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\AddPostMoveArtifactFeedbackStub;
use Tuleap\Tracker\Test\Stub\CollectDryRunTypingFieldStub;
use Tuleap\Tracker\Test\Stub\FeedbackFieldCollectorInterfaceStub;
use Tuleap\Tracker\Test\Stub\MoveArtifactByDuckTypingStub;
use Tuleap\Tracker\Test\Stub\MoveArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveAnArtifactLinkFieldStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Tracker\Action\BuildArtifactLinksMappingForDuckTypedMoveStub;

final class RestArtifactMoverTest extends TestCase
{
    use ForgeConfigSandbox;

    private AddPostMoveArtifactFeedback $post_move_action;
    private MoveArtifactByDuckTyping $mega_mover;
    private MoveRestArtifact $artifact_move;
    private FeedbackFieldCollectorInterface $feedback_collector;
    private MoveArtifact $move_action;
    private CollectDryRunTypingField $dry_run_collector;

    protected function setUp(): void
    {
        $this->move_action        = MoveArtifactStub::andReturnRemainingDeletions();
        $this->post_move_action   = AddPostMoveArtifactFeedbackStub::build();
        $this->mega_mover         = MoveArtifactByDuckTypingStub::withReturnRandomLimit();
        $this->feedback_collector = FeedbackFieldCollectorInterfaceStub::withFields([], [], []);

        $source_title_field      = TrackerFormElementStringFieldBuilder::aStringField(1)->withName("title")->build();
        $destination_title_field = TrackerFormElementStringFieldBuilder::aStringField(2)->withName("title")->build();
        $dry_run_collector       = CollectDryRunTypingFieldStub::withCollectionOfField(
            DuckTypedMoveFieldCollection::fromFields(
                [$source_title_field],
                [],
                [],
                [
                    FieldMapping::fromFields(
                        $source_title_field,
                        $destination_title_field
                    ),
                ]
            )
        );

        $this->artifact_move = new RestArtifactMover(
            $this->move_action,
            $this->post_move_action,
            $this->mega_mover,
            $this->feedback_collector,
            $dry_run_collector,
            BuildArtifactLinksMappingForDuckTypedMoveStub::withMapping([])
        );
    }

    public function testItMoveArtifactBasedOnSemanticsModeWithoutFeedback(): void
    {
        \ForgeConfig::set('feature_flag_rollback_to_semantic_move_artifact', "1");

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
        \ForgeConfig::set('feature_flag_rollback_to_semantic_move_artifact', "1");

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
        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(1)->inTracker($source_tracker)->build();
        $user           = UserTestBuilder::anActiveUser()->build();
        $this->artifact_move->move($source_tracker, $target_tracker, $artifact, $user, true);

        self::assertSame(0, $this->move_action->getCallCount());
        self::assertSame(1, $this->post_move_action->getCallCount());
        self::assertSame(1, $this->mega_mover->getCallCount());
    }

    public function testItThrowsWhenNoFieldsCanBeMoved(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->build();
        $target_tracker = TrackerTestBuilder::aTracker()->build();
        $artifact       = ArtifactTestBuilder::anArtifact(1)->inTracker($source_tracker)->build();
        $user           = UserTestBuilder::anActiveUser()->build();

        $this->expectException(MoveArtifactNoValuesToProcessException::class);

        $artifact_move = new RestArtifactMover(
            $this->move_action,
            $this->post_move_action,
            $this->mega_mover,
            $this->feedback_collector,
            CollectDryRunTypingFieldStub::withCollectionOfField(
                DuckTypedMoveFieldCollection::fromFields([], [], [], [])
            ),
            new DuckTypedMoveArtifactLinksMappingBuilder(
                RetrieveAnArtifactLinkFieldStub::withoutAnArtifactLinkField(),
                RetrieveForwardLinksStub::withoutLinks()
            )
        );

        $artifact_move->move($source_tracker, $target_tracker, $artifact, $user, true);

        self::assertSame(0, $this->move_action->getCallCount());
        self::assertSame(1, $this->post_move_action->getCallCount());
        self::assertSame(1, $this->mega_mover->getCallCount());
    }
}
