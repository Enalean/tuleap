<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Artifact_ChangesetJsonPresenter
{
    /** @var Tracker_Artifact_Changeset */
    private $changeset;

    public function __construct(Tracker_Artifact_Changeset $changeset)
    {
        $this->changeset = $changeset;
    }

    public function author_updated()
    {
        $user_str = UserHelper::instance()->getDisplayNameFromUserId($this->changeset->getSubmittedBy());
        return $GLOBALS['Language']->getText('plugin_tracker', 'artifact_update_popup_title', array($user_str));
    }

    public function time()
    {
        return DateHelper::timeAgoInWords($this->changeset->getSubmittedOn());
    }

    public function there_are_comments_and_diff()
    {
        return $this->changeset->getComment() && $this->changeset->diffToPrevious();
    }

    public function comment()
    {
        $comment = $this->changeset->getComment();
        if ($comment) {
            return $comment->fetchFollowUp();
        }
    }

    public function diff()
    {
        return $this->changeset->diffToPrevious();
    }

    public function got_it()
    {
        return $GLOBALS['Language']->getText('plugin_tracker', 'artifact_update_popup_got_it');
    }
}
