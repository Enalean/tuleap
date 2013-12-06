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

namespace Tuleap\Tracker\REST;

use Tuleap\REST\JsonCast;
use \Tracker_Artifact_Changeset;
use \Tracker_Artifact_Changeset_Comment;

class ChangesetRepresentation {
    const ROUTE = 'changesets';

    /**
     * @var int ID of the changeset
     */
    public $id;

    /**
     * @var int Who made the change
     */
    public $submitted_by;

    /**
     * @var string Date of the change
     */
    public $submitted_on;

    /**
     * @var string Email of the user who made the change (if anonymous)
     */
    public $email;

    /**
     * @var Tuleap\Tracker\REST\ChangesetCommentRepresentation Comment set by submitter (last version of the comment if several)
     */
    public $last_comment;

    /**
     * @var array Field values
     */
    public $values = array();

    public function build(Tracker_Artifact_Changeset $changeset, Tracker_Artifact_Changeset_Comment $last_comment, array $values) {
        $this->id           = JsonCast::toInt($changeset->getId());
        $this->submitted_by = JsonCast::toInt($changeset->getSubmittedBy());
        $this->submitted_on = JsonCast::toDate($changeset->getSubmittedOn());
        $this->email        = $changeset->getEmail();

        $this->last_comment = new ChangesetCommentRepresentation();
        $this->last_comment->build($last_comment);

        $this->values             = $values;
        $this->last_modified_date = JsonCast::toDate($changeset->getComment()->getSubmittedOn());
    }
}
