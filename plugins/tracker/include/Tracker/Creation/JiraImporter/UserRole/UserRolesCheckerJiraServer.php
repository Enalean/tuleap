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

final class UserRolesCheckerJiraServer implements UserRolesCheckerInterface
{
    private const PERMISSIONS_KEY  = 'permissions';
    private const PERMISSION_NAMES = [
        'PROJECT_ADMIN',
        'ADMINISTER_PROJECTS',
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
        $user_role_url = ClientWrapper::JIRA_CORE_BASE_URL . '/mypermissions?projectKey=' . urlencode($jira_project);
        $logger->debug('  GET ' . $user_role_url);

        $user_permissions = $jira_client->getUrl($user_role_url);
        if ($user_permissions === null) {
            throw new UserRolesResponseNotWellFormedException('JiraServer user permission data is null');
        }

        if (! isset($user_permissions[self::PERMISSIONS_KEY])) {
            throw new UserRolesResponseNotWellFormedException('JiraServer user permissions key `' . self::PERMISSIONS_KEY . '` not found');
        }
        foreach ($user_permissions[self::PERMISSIONS_KEY] as $permission_key => $permission_object) {
            if (in_array($permission_key, self::PERMISSION_NAMES, true)) {
                $logger->info('User is project administrator.');
                return;
            }
        }
        throw new UserIsNotProjectAdminException('User is not project administrator.');
    }
}
