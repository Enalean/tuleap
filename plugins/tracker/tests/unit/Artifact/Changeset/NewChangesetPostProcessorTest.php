<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use DateTimeImmutable;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreation;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\Changeset\PostCreation\PostCreationActionsQueuerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewChangesetPostProcessorTest extends TestCase
{
    private EventDispatcherInterface $event_manager;
    private ChangesetCommentIndexer&MockObject $changeset_comment_index;
    private PostCreationActionsQueuer $post_action_queuer;
    private Tracker_Artifact_Changeset $changeset;
    private CommentCreation $comment_creation;
    private Artifact $artifact;
    private PFUser $user;


    protected function setUp(): void
    {
        $this->event_manager           = EventDispatcherStub::withIdentityCallback();
        $this->changeset_comment_index = $this->createMock(ChangesetCommentIndexer::class);
        $this->post_action_queuer      = PostCreationActionsQueuerStub::doNothing();

        $this->artifact         = ArtifactTestBuilder::anArtifact(100)->build();
        $this->changeset        = ChangesetTestBuilder::aChangeset(1)->ofArtifact($this->artifact)->withTextComment('@peralta and @holt')->build();
        $this->user             = UserTestBuilder::anActiveUser()->build();
        $this->comment_creation = CommentCreation::fromNewComment(
            NewComment::fromParts('@peralta and @holt', CommentFormatIdentifier::TEXT, UserTestBuilder::buildWithDefaults(), 1, []),
            (int) $this->changeset->getId(),
            new CreatedFileURLMapping()
        );
    }

    private function postProcessCreation(NewChangesetCreated $changeset_created, PostCreationContext $creation_context): void
    {
        (new NewChangesetPostProcessor(
            $this->event_manager,
            $this->post_action_queuer,
            $this->changeset_comment_index,
        ))->postProcessCreation($changeset_created, $this->artifact, $creation_context, null, $this->user);
    }

    public function testItLaunchFTSUpdate(): void
    {
        $new_changeset_created = new NewChangesetCreated(
            $this->changeset,
            true,
            $this->comment_creation
        );

        $this->changeset_comment_index->expects($this->once())->method('indexNewChangesetComment');

        $this->postProcessCreation(
            $new_changeset_created,
            PostCreationContext::withConfig(
                new TrackerXmlImportConfig(
                    $this->user,
                    new DateTimeImmutable(),
                    MoveImportConfig::buildForMoveArtifact(false, [])
                ),
                false
            ),
        );
    }

    public function testLaunchPostCreationWhenImportDoesNotComeFromXML(): void
    {
        $new_changeset_created    = new NewChangesetCreated(
            $this->changeset,
            false,
            $this->comment_creation
        );
        $this->post_action_queuer = PostCreationActionsQueuerStub::withParameterAssertionCallbackHelper(
            function (Tracker_Artifact_Changeset $changeset, bool $send_notifications) {
                self::assertFalse($send_notifications);
                self::assertSame($changeset, $this->changeset);
            }
        );

        $this->postProcessCreation(
            $new_changeset_created,
            PostCreationContext::withNoConfig(false),
        );

        self::assertEquals(1, $this->post_action_queuer->getCount());
    }
}
