<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\User;

class JiraUserOnTuleapCache
{
    /**
     * @var array<string, \PFUser>
     */
    private array $user_cache = [];

    public function __construct(private JiraTuleapUsersMapping $jira_tuleap_users_mapping, private \PFUser $forge_user)
    {
    }

    public function cacheUser(\PFUser $tuleap_user, JiraUser $jira_user): void
    {
        $this->user_cache[$jira_user->getUniqueIdentifier()] = $tuleap_user;
        $this->jira_tuleap_users_mapping->addUserMapping($jira_user, $tuleap_user);
    }

    public function isUserCached(JiraUser $jira_user): bool
    {
        return $this->hasUserWithUniqueIdentifier($jira_user->getUniqueIdentifier());
    }

    public function getUserFromCache(JiraUser $jira_user): \PFUser
    {
        return $this->getUserFromCacheByJiraUniqueIdentifier($jira_user->getUniqueIdentifier());
    }

    public function getJiraTuleapUsersMapping(): JiraTuleapUsersMapping
    {
        return $this->jira_tuleap_users_mapping;
    }

    public function getUserFromCacheByJiraUniqueIdentifier(string $unique_identifier): \PFUser
    {
        if (! $this->hasUserWithUniqueIdentifier($unique_identifier)) {
            return $this->forge_user;
        }

        return $this->user_cache[$unique_identifier];
    }

    public function hasUserWithUniqueIdentifier(string $unique_identifier): bool
    {
        return isset($this->user_cache[$unique_identifier]);
    }
}
