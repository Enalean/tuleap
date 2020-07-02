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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;

class StatusValuesCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var StatusValuesCollection
     */
    private $collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $wrapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrapper = Mockery::mock(ClientWrapper::class);
        $this->logger  = Mockery::mock(LoggerInterface::class);

        $this->collection = new StatusValuesCollection(
            $this->wrapper,
            $this->logger
        );
    }

    public function testItInitsCollections(): void
    {
        $jira_project_key = 'key';
        $jira_issue_type  = 'bug';

        $this->logger->shouldReceive('debug');

        $this->wrapper->shouldReceive('getUrl')->with('project/key/statuses')->andReturn($this->getAPIResponse());

        $this->collection->initCollectionForProjectAndIssueType(
            $jira_project_key,
            $jira_issue_type
        );

        $this->assertCount(3, $this->collection->getAllValues());
        $this->assertCount(2, $this->collection->getOpenValues());
        $this->assertCount(1, $this->collection->getClosedValues());
    }

    private function getAPIResponse(): array
    {
        return [
            [
                'self' => 'URL/rest/api/latest/issuetype/10002',
                'id' => '10002' ,
                'name' => 'bug' ,
                'subtask' => false,
                'statuses' => [
                    [
                        'self' => 'URL/rest/api/latest/status/10000',
                        'description' => '' ,
                        'iconUrl' => 'URL/' ,
                        'name' => 'To Do',
                        'untranslatedName' => 'To Do',
                        'id' => '10000',
                        'statusCategory' => [
                            'self' => 'URL/rest/api/latest/statuscategory/2',
                            'id' => 2,
                            'key' => 'new' ,
                            'colorName' => 'blue-gray',
                            'name' => 'To Do',
                        ]
                    ],
                    [
                        'self' => 'URL/rest/api/latest/status/3',
                        'description' => 'This issue is being actively worked on at the moment by the assignee.',
                        'iconUrl' => 'URL/images/icons/statuses/inprogress.png',
                        'name' => 'In Progress',
                        'untranslatedName' => 'In Progress',
                        'id' => '3',
                        'statusCategory' => [
                            'self' => 'URL/rest/api/latest/statuscategory/4',
                            'id' => 4,
                            'key' => 'indeterminate',
                            'colorName' => 'yellow',
                            'name' => 'In Progress'
                        ]
                    ],
                    [
                        'self' => 'URL/rest/api/latest/status/10001',
                        'description' => '',
                        'iconUrl' => 'URL/',
                        'name' => 'Done',
                        'untranslatedName' => 'Done',
                        'id' => '10001',
                        'statusCategory' => [
                            'self' => 'URL/rest/api/latest/statuscategory/3',
                            'id' => 3,
                            'key' => 'done',
                            'colorName' => 'green',
                            'name' => 'Done',
                        ]
                    ]
                ]

            ]
        ];
    }
}
