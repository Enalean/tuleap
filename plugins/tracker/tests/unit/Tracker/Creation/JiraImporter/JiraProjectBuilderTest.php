<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tracker\Creation\JiraImporter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectCollection;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraServerClientStub;
use function PHPUnit\Framework\assertEquals;

final class JiraProjectBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsRecursivelyProjects(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=0' => [
                    'isLast'     => false,
                    'maxResults' => 2,
                    'startAt'    => 0,
                    'values'     => [
                        [
                            'key'  => "TO",
                            'name' => 'toto',
                        ],
                    ],
                ],
                ClientWrapper::JIRA_CORE_BASE_URL . "/project/search?startAt=1" => [
                    'isLast'     => true,
                    'maxResults' => 2,
                    'startAt'    => 1,
                    'values'     => [
                        [
                            'key'  => "TU",
                            'name' => 'tutu',
                        ],
                    ],
                ],
            ];
        };

        $expected_collection = new JiraProjectCollection();
        $expected_collection->addProject(
            [
                'id'    => "TO",
                'label' => "toto",
            ]
        );
        $expected_collection->addProject(
            [
                'id'    => "TU",
                'label' => "tutu",
            ]
        );

        $builder = new JiraProjectBuilder();
        $result  = $builder->build($wrapper, new NullLogger());

        self::assertEquals($expected_collection->getJiraProjects(), $result);
    }

    public function testItThrowsAndExceptionIfRecursiveCallGoesWrong(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=0' => [
                    'isLast'     => false,
                    'maxResults' => 2,
                    'startAt'    => 0,
                    'values'     => [
                        [
                            'key'  => "TO",
                            'name' => 'toto',
                        ],
                    ],
                ],
                ClientWrapper::JIRA_CORE_BASE_URL . "/project/search?startAt=1" => null,
            ];
        };

        $this->expectException(UnexpectedFormatException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($wrapper, new NullLogger());
    }

    public function testItThrowsALogicExceptionIfJiraAPIHaveChanged(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=0' => [
                    'isLast'     => false,
                    'maxResults' => 2,
                    'startAt'    => 0,
                    'values'     => [
                        [
                            'key'  => "TO",
                            'dsdsdsds' => 'toto',
                        ],
                    ],
                ],
                ClientWrapper::JIRA_CORE_BASE_URL . "/project/search?startAt=1" => null,
            ];
        };

        $this->expectException(UnexpectedFormatException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($wrapper, new NullLogger());
    }

    public function testItIteratesOverJiraServerPayload(): void
    {
        $jira_client = new class extends JiraServerClientStub
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/api/2/project', $url);

                return [
                    [
                        'key'  => 'MPN',
                        'name' => 'My project name',
                    ],
                    [
                        'key' => 'SP',
                        'name' => 'Scrum Project',
                    ],
                ];
            }
        };

        $builder = new JiraProjectBuilder();
        $result  = $builder->build($jira_client, new NullLogger());

        self::assertEquals(
            (new JiraProjectCollection())
                ->addProject(['id' => 'MPN', 'label' => 'My project name'])
                ->addProject(['id' => 'SP', 'label' => 'Scrum Project'])
                ->getJiraProjects(),
            $result
        );
    }

    public function testItCatchesMissingMandatoryInfoInJiraServerPayload(): void
    {
        $jira_client = new class extends JiraServerClientStub
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/api/2/project', $url);

                return [
                    [
                        'name' => 'My project name',
                    ],
                ];
            }
        };

        $this->expectException(UnexpectedFormatException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($jira_client, new NullLogger());
    }
}
