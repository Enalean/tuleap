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

use Tuleap\DB\DataAccessObject;

class RecipientUserDAO extends DataAccessObject
{
    /**
     * @psalm-return list<array{email:string}>
     */
    public function searchRecipientsWithAdditionalCommunityMailingsSubscribers(): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT lcase(email) AS email
             FROM user
             WHERE (status='A' OR status='R')
                AND mail_va=1"
        );
    }

    /**
     * @psalm-return list<array{email:string}>
     */
    public function searchRecipientsWithSiteUpdatesSubscribers(): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT lcase(email) AS email
             FROM user
             WHERE (status='A' OR status='R')
                AND mail_siteupdates=1"
        );
    }

    /**
     * @psalm-return list<array{email:string}>
     */
    public function searchRecipientsAllUsers(): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT lcase(email) AS email
             FROM user
             WHERE (status='A' OR status='R')"
        );
    }

    /**
     * @psalm-return list<array{email:string}>
     */
    public function searchRecipientsWithProjectAdministrators(): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT lcase(user.email) AS email
             FROM user,user_group
             WHERE user.user_id=user_group.user_id
                AND (user.status='A' OR user.status='R')
                AND user_group.admin_flags='A'"
        );
    }

    /**
     * @psalm-return list<array{email:string}>
     */
    public function searchRecipientsWithPlatformAdministrators(): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT lcase(user.email) AS email
             FROM user,user_group
             WHERE user.user_id=user_group.user_id
                AND (user.status='A' OR user.status='R')
                AND user_group.group_id=1"
        );
    }

    /**
     * @psalm-return list<array{email:string}>
     */
    public function searchRecipientsWithProjectDevelopers(): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT lcase(user.email) AS email
             FROM user,user_group
             WHERE user.user_id=user_group.user_id
                AND (user.status='A' OR user.status='R')"
        );
    }
}
