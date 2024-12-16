<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\Notification\Mention;

use Tuleap\User\RetrieveUserByUserName;

final readonly class MentionedUserInTextRetriever
{
    public function __construct(private RetrieveUserByUserName $user_retriever)
    {
    }

    public function getMentionedUsers(string $text): MentionedUserCollection
    {
        $mentioned_users = [];
        preg_match_all('/(?:^|\W)@([a-zA-Z][a-zA-Z0-9\-_\.]{2,})/', $text, $mentioned_users);

        if (\count($mentioned_users) === 0) {
            return new MentionedUserCollection([]);
        }
        $usernames = $mentioned_users[1];

        $users = [];
        foreach ($usernames as $username) {
            $user = $this->user_retriever->getUserByUserName($username);
            if ($user === null) {
                continue;
            }
            $users[] = $user;
        }
        return new MentionedUserCollection($users);
    }
}
