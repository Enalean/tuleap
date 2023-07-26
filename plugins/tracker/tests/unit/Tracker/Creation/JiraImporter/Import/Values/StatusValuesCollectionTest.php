<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Values;

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;

class StatusValuesCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItInitsCollectionsForIssueTypeInProject(): void
    {
        $jira_project_key = 'key';
        $jira_issue_type  = '10002';

        $wrapper = $this->buildJiraClientWrapperAndResponse();

        $collection = new StatusValuesCollection(
            $wrapper,
            new NullLogger()
        );
        $collection->initCollectionForProjectAndIssueType(
            $jira_project_key,
            $jira_issue_type,
            new FieldAndValueIDGenerator(),
        );

        self::assertCount(3, $collection->getAllValues());
        self::assertCount(2, $collection->getOpenValues());
        self::assertCount(1, $collection->getClosedValues());
        self::assertEquals(ClientWrapper::JIRA_CORE_BASE_URL . '/project/key/statuses', $wrapper->url);
    }

    public function testItInitsCollectionsForProject(): void
    {
        $jira_project_key = 'key';

        $wrapper = $this->buildJiraClientWrapperAndResponse();

        $collection = new StatusValuesCollection(
            $wrapper,
            new NullLogger()
        );
        $collection->initCollectionForProject(
            $jira_project_key,
            new FieldAndValueIDGenerator(),
        );

        self::assertCount(4, $collection->getAllValues());
        self::assertCount(2, $collection->getOpenValues());
        self::assertCount(2, $collection->getClosedValues());
        self::assertEquals(ClientWrapper::JIRA_CORE_BASE_URL . '/project/key/statuses', $wrapper->url);
    }

    private function buildJiraClientWrapperAndResponse(): JiraCloudClientStub
    {
        return new class extends JiraCloudClientStub {
            public string $url = '';

            public function getUrl(string $url): ?array
            {
                $this->url = $url;
                return [
                    [
                        'self' => 'URL/rest/api/3/issuetype/10002',
                        'id' => '10002',
                        'name' => 'bug',
                        'subtask' => false,
                        'statuses' => [
                            [
                                'self' => 'URL/rest/api/3/status/10000',
                                'description' => '',
                                'iconUrl' => 'URL/',
                                'name' => 'To Do',
                                'untranslatedName' => 'To Do',
                                'id' => '10000',
                                'statusCategory' => [
                                    'self' => 'URL/rest/api/3/statuscategory/2',
                                    'id' => 2,
                                    'key' => 'new',
                                    'colorName' => 'blue-gray',
                                    'name' => 'To Do',
                                ],
                            ],
                            [
                                'self' => 'URL/rest/api/3/status/3',
                                'description' => 'This issue is being actively worked on at the moment by the assignee.',
                                'iconUrl' => 'URL/images/icons/statuses/inprogress.png',
                                'name' => 'In Progress',
                                'untranslatedName' => 'In Progress',
                                'id' => '3',
                                'statusCategory' => [
                                    'self' => 'URL/rest/api/3/statuscategory/4',
                                    'id' => 4,
                                    'key' => 'indeterminate',
                                    'colorName' => 'yellow',
                                    'name' => 'In Progress',
                                ],
                            ],
                            [
                                'self' => 'URL/rest/api/3/status/10001',
                                'description' => '',
                                'iconUrl' => 'URL/',
                                'name' => 'Done',
                                'untranslatedName' => 'Done',
                                'id' => '10001',
                                'statusCategory' => [
                                    'self' => 'URL/rest/api/3/statuscategory/3',
                                    'id' => 3,
                                    'key' => 'done',
                                    'colorName' => 'green',
                                    'name' => 'Done',
                                ],
                            ],
                        ],
                    ],
                    [
                        'self' => 'URL/rest/api/3/issuetype/10003',
                        'id' => '10003',
                        'name' => 'story',
                        'subtask' => false,
                        'statuses' => [
                            [
                                'self' => 'URL/rest/api/3/status/10000',
                                'description' => '',
                                'iconUrl' => 'URL/',
                                'name' => 'To Do',
                                'untranslatedName' => 'To Do',
                                'id' => '10000',
                                'statusCategory' => [
                                    'self' => 'URL/rest/api/3/statuscategory/2',
                                    'id' => 2,
                                    'key' => 'new',
                                    'colorName' => 'blue-gray',
                                    'name' => 'To Do',
                                ],
                            ],
                            [
                                'self' => 'URL/rest/api/3/status/10001',
                                'description' => '',
                                'iconUrl' => 'URL/',
                                'name' => 'Delivered',
                                'untranslatedName' => 'Delivered',
                                'id' => '10010',
                                'statusCategory' => [
                                    'self' => 'URL/rest/api/3/statuscategory/3',
                                    'id' => 3,
                                    'key' => 'done',
                                    'colorName' => 'green',
                                    'name' => 'Done',
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };
    }
}
