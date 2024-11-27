<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Recipient;

use Tracker_Artifact_Changeset;
use Tuleap\User\RetrieveUserByUserName;

final readonly class MentionedUserInCommentRetriever
{
    public function __construct(private RetrieveUserByUserName $user_manager)
    {
    }

    public function getMentionedUsers(Tracker_Artifact_Changeset $changeset): MentionedUserCollection
    {
        $comment_changeset = $changeset->getComment();
        if ($comment_changeset === null || $comment_changeset->hasEmptyBody()) {
            return new MentionedUserCollection([]);
        }

        $comment = $comment_changeset->body;
        preg_match_all('/(?:^|\W)@([a-zA-Z][a-zA-Z0-9\-_\.]{2,})/', $comment, $mentioned_users);

        if (\count($mentioned_users) === 0) {
            return new MentionedUserCollection([]);
        }
        $usernames = $mentioned_users[1];

        $users = [];
        foreach ($usernames as $username) {
            $user = $this->user_manager->getUserByUserName($username);
            if ($user === null) {
                continue;
            }
            $users[] = $user;
        }
        return new MentionedUserCollection($users);
    }
}
