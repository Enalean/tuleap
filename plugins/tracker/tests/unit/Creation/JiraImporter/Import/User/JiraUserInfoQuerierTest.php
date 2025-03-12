<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\User;

use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraServerClientStub;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraUserInfoQuerierTest extends TestCase
{
    public function testItFetchesJiraCloudUserInfoBasedOnAccountId(): void
    {
        $client  = JiraCloudClientStub::aJiraCloudClient([
            '/rest/api/2/user?accountId=e8a4sd5d6' => [
                'displayName'  => 'Jeannot',
                'accountId'    => 'e8a4sd5d6',
                'emailAddress' => 'john.doe@example.com',
            ],
        ]);
        $querier = new JiraUserInfoQuerier($client, new NullLogger());

        $jira_cloud_user = $querier->retrieveUserFromJiraAPI('e8a4sd5d6');

        assertEquals('Jeannot', $jira_cloud_user->getDisplayName());
        assertEquals('e8a4sd5d6', $jira_cloud_user->getJiraAccountId());
    }

    public function testItFetchesJiraCloudUserInfo(): void
    {
        $client  = JiraServerClientStub::aJiraServerClient([
            '/rest/api/2/user?key=john.doe%40example.com' => [
                'self'         => 'https://jira.example.com/rest/api/2/user?username=john.doe%40example.com',
                'displayName'  => 'Jeannot',
                'name'         => 'john.doe%40example.com',
                'emailAddress' => 'john.doe@example.com',
            ],
        ]);
        $querier = new JiraUserInfoQuerier($client, new NullLogger());

        $jira_cloud_user = $querier->retrieveUserFromJiraAPI('john.doe%40example.com');

        assertEquals('Jeannot', $jira_cloud_user->getDisplayName());
        assertEquals('john.doe%40example.com', $jira_cloud_user->getUniqueIdentifier());
    }
}
