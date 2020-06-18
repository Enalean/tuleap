<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use DateTimeImmutable;
use PFUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\Comment;

class Snapshot
{
    /**
     * @var FieldSnapshot[]
     * @psalm-readonly
     */
    private $field_snapshots;

    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;

    /**
     * @var DateTimeImmutable
     * @psalm-readonly
     */
    private $date;

    /**
     * @var Comment|null
     * @psalm-readonly
     */
    private $comment_snapshot;

    public function __construct(
        PFUser $user,
        DateTimeImmutable $date,
        array $field_snapshots,
        ?Comment $comment_snapshot
    ) {
        $this->user             = $user;
        $this->date             = $date;
        $this->field_snapshots  = $field_snapshots;
        $this->comment_snapshot = $comment_snapshot;
    }

    /**
     * @psalm-mutation-free
     * @return FieldSnapshot[]
     */
    public function getAllFieldsSnapshot(): array
    {
        return $this->field_snapshots;
    }

    /**
     * @psalm-mutation-free
     */
    public function getFieldInSnapshot(string $field_key): ?FieldSnapshot
    {
        foreach ($this->field_snapshots as $field_snapshot) {
            if ($field_snapshot->getFieldMapping()->getJiraFieldId() === $field_key) {
                return $field_snapshot;
            }
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getCommentSnapshot(): ?Comment
    {
        return $this->comment_snapshot;
    }
}
