<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment;

use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\UniDiffLine;

class InlineCommentUpdater
{

    /**
     * @param array       $comments      The comments to update
     * @param FileUniDiff $original_diff The unidiff that the inline comments were referring to
     * @param FileUniDiff $changes_diff  The unidiff between the old state and the new state
     *                                    of the file in the source branch
     * @param FileUniDiff $targeted_diff The unidiff between the new state of the file in the source branch
     *                                    and the state of the file in the destination branch
     *
     * @return the list of comments to update
     */
    public function updateWhenSourceChanges(array $comments, FileUniDiff $original_diff, FileUniDiff $changes_diff, FileUniDiff $targeted_diff)
    {
        $comments_to_update = array();

        foreach ($comments as $comment) {
            $original_line = $original_diff->getLine($comment->getUniDiffOffset());
            if ($original_line->getType() == UniDiffLine::ADDED || $original_line->getType() == UniDiffLine::KEPT) {
                $changes_line = $changes_diff->getLineFromOldOffset($original_line->getNewOffset());
                if ($changes_line->getType() == UniDiffLine::REMOVED) {
                    $comment->markAsOutdated();
                } else {
                    $new_unidiff_offset = $targeted_diff->getLineFromNewOffset($changes_line->getNewOffset())->getUnidiffOffset();
                    $comment->setUnidiffOffset($new_unidiff_offset);
                }
            } else if ($original_line->getType() == UnidiffLine::REMOVED) {
                $targeted_line = $targeted_diff->getLineFromOldOffset($original_line->getOldOffset());
                if ($targeted_line->getType() == UnidiffLine::REMOVED) {
                    $new_unidiff_offset = $targeted_line->getUnidiffOffset();
                    $comment->setUnidiffOffset($new_unidiff_offset);
                } else {
                    $comment->markAsOutdated();
                }
            }
            $comments_to_update[] = $comment;
        }

        return $comments_to_update;
    }

    /**
     * @param array       $comments      The comments to update
     * @param FileUniDiff $original_diff The unidiff that the inline comments were referring to
     * @param FileUniDiff $changes_diff  The unidiff between the old state and the new state
     *                                    of the file in the destination branch
     * @param FileUniDiff $targeted_diff The unidiff between the new state of the file in the source branch
     *                                    and the state of the file in the destination branch
     *
     * @return the list of comments to update
     */
    public function updateOnRebase(array $comments, FileUniDiff $original_diff, FileUniDiff $changes_diff, FileUniDiff $targeted_diff)
    {
        $comments_to_update = array();

        foreach ($comments as $comment) {
            $original_line = $original_diff->getLine($comment->getUniDiffOffset());
            if ($original_line->getType() == UniDiffLine::KEPT) {
                $changes_line = $changes_diff->getLineFromOldOffset($original_line->getOldOffset());
                if ($changes_line->getType() == UniDiffLine::REMOVED) {
                    $comment->markAsOutdated();
                } else {
                    $new_unidiff_offset = $targeted_diff->getLineFromOldOffset($changes_line->getNewOffset())->getUnidiffOffset();
                    $comment->setUnidiffOffset($new_unidiff_offset);
                }
            } else {
                $comment->markAsOutdated();
            }
            $comments_to_update[] = $comment;
        }

        return $comments_to_update;
    }

}
