<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Comment;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetCommentIndexerTest extends TestCase
{
    public function testIndexesCommentFromChangeset(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();

        $event_dispatcher          = EventDispatcherStub::withCallback(
            static function (ItemToIndex $item_to_index) use ($artifact): ItemToIndex {
                self::assertEquals(
                    new ItemToIndex(
                        'plugin_artifact_changeset_comment',
                        (int) $artifact->getTracker()->getGroupId(),
                        'Some comment',
                        'plaintext',
                        [
                            'changeset_id' => '888',
                            'artifact_id'  => (string) $artifact->getId(),
                            'tracker_id'  => (string) $artifact->getTracker()->getId(),
                        ]
                    ),
                    $item_to_index
                );
                return $item_to_index;
            }
        );
        $changeset_comment_dao     = $this->createStub(\Tracker_Artifact_Changeset_CommentDao::class);
        $changeset_comment_indexer = self::buildChangesetCommentIndexer($event_dispatcher, $changeset_comment_dao);

        $changeset = $this->createStub(Tracker_Artifact_Changeset::class);
        $changeset->method('getId')->willReturn('888');
        $changeset_comment_dao->method('searchLastVersion')->willReturn(\TestHelper::arrayToDar(['body' => 'Some comment', 'body_format' => CommentFormatIdentifier::TEXT->value]));
        $changeset->method('getArtifact')->willReturn($artifact);

        $changeset_comment_indexer->indexChangesetCommentFromChangeset($changeset);

        self::assertEquals(1, $event_dispatcher->getCallCount());
    }

    public function testIndexesNewComment(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();

        $event_dispatcher          = EventDispatcherStub::withCallback(
            static function (ItemToIndex $item_to_index) use ($artifact): ItemToIndex {
                self::assertEquals(
                    new ItemToIndex(
                        'plugin_artifact_changeset_comment',
                        (int) $artifact->getTracker()->getGroupId(),
                        '',
                        'commonmark',
                        [
                            'changeset_id' => '889',
                            'artifact_id'  => (string) $artifact->getId(),
                            'tracker_id'  => (string) $artifact->getTracker()->getId(),
                        ]
                    ),
                    $item_to_index
                );
                return $item_to_index;
            }
        );
        $changeset_comment_indexer = self::buildChangesetCommentIndexer($event_dispatcher, $this->createStub(\Tracker_Artifact_Changeset_CommentDao::class));

        $comment_creation = CommentCreation::fromNewComment(
            NewComment::buildEmpty(UserTestBuilder::buildWithDefaults(), 1),
            889,
            new CreatedFileURLMapping()
        );

        $changeset_comment_indexer->indexNewChangesetComment(
            $comment_creation,
            $artifact,
        );

        self::assertEquals(1, $event_dispatcher->getCallCount());
    }

    public function testDoesNothingWhenNoCommentIsAttachedToTheChangeset(): void
    {
        $event_dispatcher          = EventDispatcherStub::withIdentityCallback();
        $changeset_comment_dao     = $this->createStub(\Tracker_Artifact_Changeset_CommentDao::class);
        $changeset_comment_indexer = self::buildChangesetCommentIndexer($event_dispatcher, $changeset_comment_dao);

        $changeset = $this->createStub(Tracker_Artifact_Changeset::class);
        $changeset->method('getId')->willReturn(404);
        $changeset_comment_dao->method('searchLastVersion')->willReturn(\TestHelper::emptyDar());

        $changeset_comment_indexer->indexChangesetCommentFromChangeset($changeset);

        self::assertEquals(0, $event_dispatcher->getCallCount());
    }

    public function testAskForDeletionFromAnArtifact(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (IndexedItemsToRemove $items_to_remove): IndexedItemsToRemove {
                self::assertEquals(
                    new IndexedItemsToRemove(
                        'plugin_artifact_changeset_comment',
                        [
                            'artifact_id' => '999',
                        ]
                    ),
                    $items_to_remove
                );
                return $items_to_remove;
            }
        );

        $indexer = self::buildChangesetCommentIndexer($event_dispatcher, $this->createStub(\Tracker_Artifact_Changeset_CommentDao::class));

        $indexer->askForDeletionOfIndexedCommentsFromArtifact(ArtifactTestBuilder::anArtifact(999)->build());
    }

    public static function buildChangesetCommentIndexer(EventDispatcherInterface $event_dispatcher, \Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao): ChangesetCommentIndexer
    {
        return new ChangesetCommentIndexer(
            new ItemToIndexQueueEventBased($event_dispatcher),
            $event_dispatcher,
            $changeset_comment_dao,
        );
    }
}
