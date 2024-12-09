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
use Tracker_Artifact_Changeset_CommentDao;
use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Tracker\Artifact\Artifact;

class ChangesetCommentIndexer
{
    public const INDEX_TYPE_CHANGESET_COMMENT = 'plugin_artifact_changeset_comment';

    public function __construct(
        private ItemToIndexQueue $index_queue,
        private EventDispatcherInterface $event_dispatcher,
        private Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
    ) {
    }

    public function indexChangesetCommentFromChangeset(\Tracker_Artifact_Changeset $changeset): void
    {
        $changeset_id = (int) $changeset->getId();
        $row          = $this->changeset_comment_dao->searchLastVersion($changeset_id)->getRow();

        if (! $row) {
            return;
        }

        $this->indexComment(
            $changeset->getArtifact(),
            (string) $changeset_id,
            $row['body'],
            CommentFormatIdentifier::fromStringWithDefault($row['body_format'])
        );
    }

    public function indexNewChangesetComment(CommentCreation $comment_creation, Artifact $artifact): void
    {
        $this->indexComment(
            $artifact,
            (string) $comment_creation->getChangesetId(),
            $comment_creation->getBody(),
            $comment_creation->getFormat(),
        );
    }

    private function indexComment(Artifact $artifact, string $changeset_id, string $comment_body, CommentFormatIdentifier $comment_format): void
    {
        $tracker                   = $artifact->getTracker();
        $comment_format_identifier = $comment_format->value;
        $this->index_queue->addItemToQueue(
            new \Tuleap\Search\ItemToIndex(
                self::INDEX_TYPE_CHANGESET_COMMENT,
                (int) $tracker->getGroupId(),
                $comment_body,
                in_array($comment_format_identifier, ItemToIndex::ALL_CONTENT_TYPES, true) ? $comment_format_identifier : ItemToIndex::CONTENT_TYPE_PLAINTEXT,
                [
                    'changeset_id' => $changeset_id,
                    'artifact_id'  => (string) $artifact->getId(),
                    'tracker_id'   => (string) $tracker->getId(),
                ]
            )
        );
    }

    public function askForDeletionOfIndexedCommentsFromArtifact(Artifact $artifact): void
    {
        $this->event_dispatcher->dispatch(
            new \Tuleap\Search\IndexedItemsToRemove(
                self::INDEX_TYPE_CHANGESET_COMMENT,
                [
                    'artifact_id' => (string) $artifact->getId(),
                ]
            )
        );
    }
}
