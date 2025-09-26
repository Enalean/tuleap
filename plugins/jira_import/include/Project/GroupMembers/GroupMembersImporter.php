<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\GroupMembers;

use Psr\Log\LoggerInterface;
use Tuleap\Project\UGroups\XML\XMLUserGroup;
use Tuleap\Project\XML\XMLUserGroups;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\GetTuleapUserFromJiraUser;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\XML\XMLUser;

final class GroupMembersImporter
{
    private const string PROJECT_ROLE_URL = '/rest/api/2/project/%s/role';

    private const string ADMINISTRATOR_ROLE = 'Administrators';

    public function __construct(private JiraClient $client, private LoggerInterface $logger, private GetTuleapUserFromJiraUser $tuleap_user_from_jira_user, private \PFUser $default_user)
    {
    }

    public function getUserGroups(string $jira_project): ?XMLUserGroups
    {
        $role_url = sprintf(self::PROJECT_ROLE_URL, urlencode($jira_project));
        $this->logger->debug('GET ' . $role_url);
        $payload = $this->client->getUrl($role_url);
        if (! $payload) {
            $this->logger->warning('No roles found');
            return null;
        }
        $groups               = [];
        $project_member_users = [];
        foreach ($payload as $group_name => $url) {
            if ($group_name === self::ADMINISTRATOR_ROLE) {
                $project_admin_users  = $this->getUsers($url);
                $groups[]             = new XMLUserGroup(\ProjectUGroup::PROJECT_ADMIN_NAME, $project_admin_users);
                $project_member_users = array_merge($project_member_users, $project_admin_users);
            } else {
                $group_members        = $this->getUsers($url);
                $groups[]             = XMLUserGroup::fromUnconstrainedName($group_name, $group_members);
                $project_member_users = array_merge($project_member_users, $group_members);
            }
        }
        $groups[] = new XMLUserGroup(\ProjectUGroup::PROJECT_MEMBERS_NAME, $project_member_users);
        return new XMLUserGroups($groups);
    }

    private function getUsers(string $role_url): array
    {
        $url_chunks = parse_url($role_url);
        if (! isset($url_chunks['path'])) {
            $this->logger->warning('Unable to parse role url (no path) ' . $role_url);
            return [];
        }
        $place = strpos($url_chunks['path'], '/rest');
        if ($place === false) {
            $this->logger->warning('Unable to parse role url (no rest) ' . $role_url);
            return [];
        }
        $role_sub_url = substr($url_chunks['path'], $place);

        $payload = $this->client->getUrl($role_sub_url);
        if (! isset($payload['actors'])) {
            $this->logger->warning('No actors in role payload');
            return [];
        }
        $users = [];
        foreach ($payload['actors'] as $user_payload) {
            $tuleap_user = $this->getUser($user_payload);
            if (! $tuleap_user) {
                continue;
            }
            $users[] = XMLUser::buildUsernameFromUser($tuleap_user);
        }
        return $users;
    }

    private function getUser(array $jira_user_payload): ?\PFUser
    {
        $user = $this->getUserFromPayload($jira_user_payload);
        if (! $user || $user->getId() === $this->default_user->getId()) {
            return null;
        }
        return $user;
    }

    private function getUserFromPayload(array $user_payload): ?\PFUser
    {
        if ($this->client->isJiraCloud()) {
            if (! isset($user_payload['actorUser']['accountId'])) {
                return null;
            }
            return $this->tuleap_user_from_jira_user->getAssignedTuleapUser($user_payload['actorUser']['accountId']);
        }
        return $this->tuleap_user_from_jira_user->getAssignedTuleapUser($user_payload['name']);
    }
}
