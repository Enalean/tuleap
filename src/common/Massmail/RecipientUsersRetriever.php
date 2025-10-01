<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Massmail;

class RecipientUsersRetriever
{
    public function __construct(private RecipientUserDAO $recipient_user_dao)
    {
    }

    /**
     * @return RecipientUser[]
     */
    public function getRecipientUsers(string $destination): array
    {
        $recipients_rows  = [];
        $recipients_users = [];
        switch ($destination) {
            case 'comm':
                $recipients_rows = $this->recipient_user_dao->searchRecipientsWithAdditionalCommunityMailingsSubscribers();
                break;
            case 'sf':
                $recipients_rows = $this->recipient_user_dao->searchRecipientsWithSiteUpdatesSubscribers();
                break;
            case 'all':
                $recipients_rows = $this->recipient_user_dao->searchRecipientsAllUsers();
                break;
            case 'admin':
                $recipients_rows = $this->recipient_user_dao->searchRecipientsWithProjectAdministrators();
                break;
            case 'sfadmin':
                $recipients_rows = $this->recipient_user_dao->searchRecipientsWithPlatformAdministrators();
                break;
            case 'devel':
                $recipients_rows = $this->recipient_user_dao->searchRecipientsWithProjectDevelopers();
                break;
        }

        foreach ($recipients_rows as $recipients_row) {
            $recipients_users[] = RecipientUser::buildFromArray($recipients_row);
        }
        return $recipients_users;
    }
}
