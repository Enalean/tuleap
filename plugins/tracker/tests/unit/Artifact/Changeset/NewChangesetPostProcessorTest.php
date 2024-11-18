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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreation;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuerStub;

final class NewChangesetPostProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EventDispatcherInterface $event_manager;
    private ChangesetCommentIndexer&\PHPUnit\Framework\MockObject\MockObject $changeset_comment_index;
    private PostCreationActionsQueuer $post_action_queuer;
    private \Tracker_Artifact_Changeset $changeset;
    private CommentCreation $comment_creation;
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private \PFUser $user;
    private NewChangesetPostProcessor $post_creation_processor;


    protected function setUp(): void
    {
        $this->event_manager           = EventDispatcherStub::withIdentityCallback();
        $this->changeset_comment_index = $this->createMock(ChangesetCommentIndexer::class);
        $this->post_action_queuer      = PostCreationActionsQueuerStub::doNothing();

        $this->artifact         = ArtifactTestBuilder::anArtifact(100)->build();
        $this->changeset        = ChangesetTestBuilder::aChangeset(1)->ofArtifact($this->artifact)->build();
        $this->user             = UserTestBuilder::anActiveUser()->build();
        $this->comment_creation = CommentCreation::fromNewComment(
            NewComment::buildEmpty(UserTestBuilder::buildWithDefaults(), 1),
            (int) $this->changeset->getId(),
            new CreatedFileURLMapping()
        );

        $this->post_creation_processor = new NewChangesetPostProcessor(
            $this->event_manager,
            $this->post_action_queuer,
            $this->changeset_comment_index,
        );
    }

    public function testItLaunchFTSUpdate(): void
    {
        $new_changeset_created = new NewChangesetCreated(
            $this->changeset,
            true,
            $this->comment_creation
        );

        $this->changeset_comment_index->expects(self::once())->method('indexNewChangesetComment');

        $this->post_creation_processor->postProcessCreation(
            $new_changeset_created,
            $this->artifact,
            PostCreationContext::withConfig(
                new TrackerXmlImportConfig(
                    $this->user,
                    new \DateTimeImmutable(),
                    MoveImportConfig::buildForMoveArtifact(false, [])
                ),
                false
            ),
            null,
            $this->user
        );
    }

    public function testLaunchPostCreationWhenImportDoesNotComeFromXML(): void
    {
        $new_changeset_created = new NewChangesetCreated(
            $this->changeset,
            false,
            $this->comment_creation
        );

        $this->post_creation_processor->postProcessCreation(
            $new_changeset_created,
            $this->artifact,
            PostCreationContext::withNoConfig(false),
            null,
            $this->user
        );

        self::assertEquals(1, $this->post_action_queuer->getCount());
    }
}
