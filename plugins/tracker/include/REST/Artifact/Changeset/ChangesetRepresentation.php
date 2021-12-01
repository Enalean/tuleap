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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact\Changeset;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
final class ChangesetRepresentation
{
    public const ROUTE = 'changesets';

    /**
     * @var int ID of the changeset
     */
    public $id;

    /**
     * @var int Who made the change
     */
    public $submitted_by;

    /**
     * @var MinimalUserRepresentation | null Representation of who made the change
     */
    public $submitted_by_details;

    /**
     * @var string Date of the change
     */
    public $submitted_on;

    /**
     * @var string | null Email of the user who made the change (if anonymous)
     */
    public $email;

    /**
     * @var CommentRepresentation | null Comment set by submitter (last version of the comment if several)
     */
    public $last_comment;

    /**
     * @var MinimalUserRepresentation | null Representation of who made the last change
     */
    public $last_modified_by;

    /**
     * @var array Field values
     */
    public $values = [];

    /**
     * @var string Date of last edit of the comment
     */
    public $last_modified_date;

    public function __construct(
        int $changeset_id,
        int $submitter_user_id,
        ?MinimalUserRepresentation $submitted_by_details,
        int $submitted_on,
        ?string $email,
        CommentRepresentation $last_comment,
        array $values,
        ?MinimalUserRepresentation $last_modified_by,
        int $last_modified_date,
    ) {
        $this->id                   = JsonCast::toInt($changeset_id);
        $this->submitted_by         = JsonCast::toInt($submitter_user_id);
        $this->submitted_by_details = $submitted_by_details;
        $this->submitted_on         = JsonCast::toDate($submitted_on);
        $this->email                = $email;
        $this->last_comment         = $last_comment;
        $this->values               = $values;
        $this->last_modified_by     = $last_modified_by;
        $this->last_modified_date   = JsonCast::toDate($last_modified_date);
    }
}
