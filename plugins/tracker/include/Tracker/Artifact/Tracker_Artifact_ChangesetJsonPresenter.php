<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Date\DateHelper;

class Tracker_Artifact_ChangesetJsonPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_time;

    /** @var Tracker_Artifact_Changeset */
    private $changeset;
    /**
     * @var PFUser
     */
    private $current_user;

    public function __construct(Tracker_Artifact_Changeset $changeset, \PFUser $current_user)
    {
        $this->changeset     = $changeset;
        $this->current_user  = $current_user;
        $this->purified_time = DateHelper::relativeDateInlineContext((int) $this->changeset->getSubmittedOn(), $this->current_user);
    }

    public function author_updated()
    {
        $user_str = UserHelper::instance()->getDisplayNameFromUserId($this->changeset->getSubmittedBy());
        return sprintf(dgettext('tuleap-tracker', '%1$s has just updated the artifact'), (string) $user_str);
    }

    public function there_are_comments_and_diff()
    {
        return $this->changeset->getComment() && $this->changeset->diffToPrevious();
    }

    public function comment()
    {
        $comment = $this->changeset->getComment();
        if ($comment) {
            return $comment->fetchFollowUp($this->current_user);
        }
    }

    public function diff()
    {
        return $this->changeset->diffToPrevious();
    }

    public function got_it()
    {
        return dgettext('tuleap-tracker', 'OK, got it!');
    }
}
