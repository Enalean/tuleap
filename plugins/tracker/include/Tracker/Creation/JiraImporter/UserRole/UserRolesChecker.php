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
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

final class UserRolesChecker implements UserRolesCheckerInterface
{
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
        $logger->debug('Check if user is administrator in Jira Project');

        $checker = match ($jira_client->isJiraCloud()) {
            true  => new UserRolesCheckerJiraCloud(),
            false => new UserRolesCheckerJiraServer(),
        };
        $checker->checkUserIsAdminOfJiraProject($jira_client, $logger, $jira_project);
    }
}
