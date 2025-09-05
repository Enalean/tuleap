<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter\UserRole;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

final class UserRolesCheckerJiraCloud implements UserRolesCheckerInterface
{
    private const NAME_KEY                 = 'name';
    private const ADMINISTRATOR_ROLE_NAMES = [
        'Administrator',
        'Administrators',
    ];

    /**
     * @throws UserRolesResponseNotWellFormedException
     * @throws UserIsNotProjectAdminException
     * @throws \JsonException
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    #[\Override]
    public function checkUserIsAdminOfJiraProject(
        JiraClient $jira_client,
        LoggerInterface $logger,
        string $jira_project,
    ): void {
        $user_role_url = ClientWrapper::JIRA_CORE_BASE_URL . '/project/' . urlencode($jira_project) . '/roledetails?currentMember=true';
        $logger->debug('  GET ' . $user_role_url);

        $user_roles_data = $jira_client->getUrl($user_role_url);
        if ($user_roles_data === null) {
            throw new UserRolesResponseNotWellFormedException('User roles data is null');
        }

        assert(is_array($user_roles_data));
        foreach ($user_roles_data as $user_role) {
            if (! isset($user_role[self::NAME_KEY])) {
                throw new UserRolesResponseNotWellFormedException('User roles key `' . self::NAME_KEY . '` not found');
            }

            if (in_array($user_role[self::NAME_KEY], self::ADMINISTRATOR_ROLE_NAMES, true)) {
                $logger->info('User is project administrator.');
                return;
            }
        }
        throw new UserIsNotProjectAdminException('User is not project administrator.');
    }
}
