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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraServerClientStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraServerChangelogEntriesBuilderTest extends TestCase
{
    public function testChangeLogFromJiraServer()
    {
        $jira_client = JiraServerClientStub::aJiraServerClient([
            ClientWrapper::JIRA_CORE_BASE_URL . '/issue/SBX-6?expand=changelog' => [
                'expand'    => 'renderedFields,names,schema,operations,editmeta,changelog,versionedRepresentations',
                'id'        => '10777',
                'self'      => 'https://jira.example.com/rest/api/2/issue/10777',
                'key'       => 'SBX-6',
                'fields'    => [
                ],
                'changelog' => [
                    'startAt'    => 0,
                    'maxResults' => 1,
                    'total'      => 1,
                    'histories'  => [
                        [
                            'id'      => '19074',
                            'author'  => [
                                'self'         => 'https://jira.example.com/rest/api/2/user?username=jdoe',
                                'name'         => 'jdoe',
                                'key'          => 'JIRAUSER10131',
                                'emailAddress' => 'john.doe@example.com',
                                'avatarUrls'   => [
                                    '48x48' => 'https://jira.example.com//secure/useravatar?avatarId=10337',
                                    '24x24' => 'https://jira.example.com//secure/useravatar?size=small&avatarId=10337',
                                    '16x16' => 'https://jira.example.com//secure/useravatar?size=xsmall&avatarId=10337',
                                    '32x32' => 'https://jira.example.com//secure/useravatar?size=medium&avatarId=10337',
                                ],
                                'displayName'  => 'DOE John',
                                'active'       => true,
                                'timeZone'     => 'Europe/Paris',
                            ],
                            'created' => '2021-03-24T11:06:46.027+0100',
                            'items'   => [
                                [
                                    'field'      => 'duedate',
                                    'fieldtype'  => 'jira',
                                    'from'       => null,
                                    'fromString' => null,
                                    'to'         => '2021-03-24',
                                    'toString'   => '2021-03-24 00:00:00.0',
                                ],
                            ],

                        ],
                    ],
                ],
            ],
        ]);

        $changelog_entries_builder = new JiraServerChangelogEntriesBuilder($jira_client, new NullLogger());

        $representations = $changelog_entries_builder->buildEntriesCollectionForIssue('SBX-6');

        self::assertCount(1, $representations);
        self::assertEquals(19074, $representations[0]->getId());
        self::assertEquals('john.doe@example.com', $representations[0]->getChangelogOwner()->getEmailAddress());
        self::assertEquals(1616580406, $representations[0]->getCreated()->getTimestamp());
        self::assertCount(1, $representations[0]->getItemRepresentations());
    }
}
