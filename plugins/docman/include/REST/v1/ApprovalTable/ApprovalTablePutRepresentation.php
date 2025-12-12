<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\ApprovalTable;

/**
 * @psalm-immutable
 */
final class ApprovalTablePutRepresentation
{
    /**
     * @var int ID of the table owner {@from body} {@required true}
     */
    public int $owner;
    /**
     * @var string Status of the table {@from body} {@required true} {@choice closed,disabled,enabled}
     */
    public string $status;
    /**
     * @var string Description of the table {@from body} {@required false}
     */
    public string $comment = '';
    /**
     * @var string How the table notify its reviewers {@from body} {@required true} {@choice disabled,all_at_once,sequential}
     */
    public string $notification_type;
    /**
     * @var list<int> List of current reviewers ids. They are ordered with their rank. It is the wanted value for the reviewer table (minus user groups added in a later parameter) with added and removed user from previous value {@from body} {@required true}
     */
    public array $reviewers;
    /**
     * @var list<int> List of user group ids to add to the reviewers (their members are added after the reviewers from <code>reviewers</code>) {@from body} {@required true}
     */
    public array $reviewers_group_to_add;
    /**
     * @var int<0, max> Amount of days between each reminder to approvers {@from body} {@required true}
     */
    public int $reminder_occurence;

    /**
     * @param list<int> $reviewers
     * @param list<int> $reviewers_group_to_add
     * @param int<0, max> $reminder_occurence
     */
    public function __construct(
        int $owner,
        string $status,
        string $comment,
        string $notification_type,
        array $reviewers,
        array $reviewers_group_to_add,
        int $reminder_occurence,
    ) {
        $this->owner                  = $owner;
        $this->status                 = $status;
        $this->comment                = $comment;
        $this->notification_type      = $notification_type;
        $this->reviewers              = $reviewers;
        $this->reviewers_group_to_add = $reviewers_group_to_add;
        $this->reminder_occurence     = $reminder_occurence;
    }
}
