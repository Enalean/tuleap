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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraUser;

class JiraUserOnTuleapCache
{
    /**
     * @var array
     */
    private $user_cache = [];

    /**
     * @var JiraTuleapUsersMapping
     */
    private $jira_tuleap_users_mapping;

    public function __construct(JiraTuleapUsersMapping $jira_tuleap_users_mapping)
    {
        $this->jira_tuleap_users_mapping = $jira_tuleap_users_mapping;
    }

    public function cacheUser(\PFUser $tuleap_user, JiraUser $jira_user): void
    {
        $this->user_cache[$jira_user->getJiraAccountId()] = $tuleap_user;
        $this->jira_tuleap_users_mapping->addUserMapping($jira_user, $tuleap_user);
    }

    public function isUserCached(JiraUser $jira_user): bool
    {
        return isset($this->user_cache[$jira_user->getJiraAccountId()]);
    }

    public function getUserFromCacheByJiraAccountId(JiraUser $jira_user): \PFUser
    {
        return $this->user_cache[$jira_user->getJiraAccountId()];
    }

    public function getJiraTuleapUsersMapping(): JiraTuleapUsersMapping
    {
        return $this->jira_tuleap_users_mapping;
    }
}
