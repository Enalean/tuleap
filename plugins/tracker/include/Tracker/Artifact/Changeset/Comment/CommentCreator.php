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

use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;

final class CommentCreator
{
    public function __construct(
        private \Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
        private \ReferenceManager $reference_manager,
        private TrackerPrivateCommentUGroupPermissionInserter $comment_ugroup_permission_inserter,
        private TextValueValidator $text_value_validator,
    ) {
    }

    /**
     * @throws \Tracker_CommentNotStoredException
     * @throws CommentContentNotValidException
     */
    public function createComment(Artifact $artifact, CommentCreation $comment): bool
    {
        //check size here.
        return $this->text_value_validator->isCommentContentValid($comment)
            ->match(function () use ($comment, $artifact) {
                $comment_added = $this->changeset_comment_dao->createNewVersion(
                    $comment->getChangesetId(),
                    $comment->getBody(),
                    $comment->getSubmitter()->getId(),
                    $comment->getSubmissionTimestamp(),
                    0,
                    (string) $comment->getFormat()
                );
                if (! $comment_added) {
                    throw new \Tracker_CommentNotStoredException(
                        dgettext('tuleap-tracker', 'The comment cannot be stored.')
                    );
                }

                if (is_int($comment_added)) {
                    $this->comment_ugroup_permission_inserter->insertUGroupsOnPrivateComment(
                        $comment_added,
                        $comment->getUserGroupsThatAreAllowedToSee()
                    );
                }

                $this->reference_manager->extractCrossRef(
                    $comment->getBody(),
                    $artifact->getId(),
                    Artifact::REFERENCE_NATURE,
                    (int) $artifact->getTracker()->getGroupID(),
                    (int) $comment->getSubmitter()->getId(),
                    $artifact->getTracker()->getItemName()
                );

                return true;
            }, function (Fault $fault) {
                throw new CommentContentNotValidException((string) $fault);
            });
    }
}
