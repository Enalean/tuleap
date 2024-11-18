<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment;

use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraCloudUser;

final class CommentTestBuilder
{
    private \DateTimeImmutable $date;
    private string $comment = 'Comment 01';

    private function __construct()
    {
    }

    public static function aJiraCloudComment(): self
    {
        return new self();
    }

    public function withDate(string $date): self
    {
        $this->date = new \DateTimeImmutable($date);
        return $this;
    }

    public function withComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function build(): Comment
    {
        return new JiraCloudComment(
            new ActiveJiraCloudUser([
                'displayName' => 'userO1',
                'accountId' => 'e12ds5123sw',
            ]),
            $this->date,
            $this->comment,
        );
    }
}
