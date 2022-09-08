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
use Tracker_Artifact_Changeset_Comment;
use Tuleap\Tracker\Artifact\Artifact;

class ChangesetCommentIndexer
{
    public const INDEX_TYPE_CHANGESET_COMMENT = 'plugin_artifact_changeset_comment';

    public function __construct(
        private EventDispatcherInterface $event_dispatcher,
        private \Codendi_HTMLPurifier $purifier,
    ) {
    }

    public function indexChangesetCommentFromChangeset(\Tracker_Artifact_Changeset $changeset): void
    {
        $comment = $changeset->getComment();

        if ($comment === null) {
            return;
        }

        $this->indexComment(
            $changeset->getArtifact(),
            $changeset->getId(),
            $comment->body,
            CommentFormatIdentifier::fromFormatString($comment->bodyFormat)
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
        $tracker = $artifact->getTracker();
        $this->event_dispatcher->dispatch(
            new \Tuleap\Search\ItemToIndex(
                self::INDEX_TYPE_CHANGESET_COMMENT,
                Tracker_Artifact_Changeset_Comment::getCommentInPlaintext($this->purifier, $comment_body, $comment_format),
                [
                    'changeset_id' => $changeset_id,
                    'artifact_id'  => (string) $artifact->getId(),
                    'tracker_id'   => (string) $tracker->getId(),
                    'project_id'   => (string) $tracker->getGroupId(),
                ]
            )
        );
    }

    public function askForDeletionOfIndexedCommentsFromProject(\Project $project): void
    {
        $this->event_dispatcher->dispatch(
            new \Tuleap\Search\IndexedItemsToRemove(
                self::INDEX_TYPE_CHANGESET_COMMENT,
                [
                    'project_id' => (string) $project->getID(),
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
