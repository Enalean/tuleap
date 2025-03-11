<?php
/**
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

namespace Tuleap\JiraImport\JiraAgile;

use Psr\Log\NullLogger;
use RuntimeException;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use Tuleap\Tracker\XML\Exporter\FieldChange\ArtifactLinkChange;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraSprintIssuesRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCatchesIfJiraReturnsAPayloadWeCannotWorkWith(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public int $called = 0;

            public function getUrl(string $url): ?array
            {
                $this->called++;

                assertEquals('/rest/agile/1.0/sprint/14/issue?fields=id&startAt=0', $url);

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'issues'     => [],
                ];
            }
        };

        $retriever = new JiraSprintIssuesRetrieverFromAPI($client, new NullLogger());

        $this->expectException(UnexpectedFormatException::class);

        $retriever->getArtifactLinkChange(JiraSprint::buildActive(14, 'Sprint 1'));

        self::assertEquals(1, $client->called);
    }

    public function testItQueriesTheURL(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public int $called = 0;

            public function getUrl(string $url): ?array
            {
                $this->called++;

                assertEquals('/rest/agile/1.0/sprint/14/issue?fields=id&startAt=0', $url);

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'total'      => 0,
                    'issues'     => [],
                ];
            }
        };

        $retriever = new JiraSprintIssuesRetrieverFromAPI($client, new NullLogger());

        $retriever->getArtifactLinkChange(JiraSprint::buildActive(14, 'Sprint 1'));

        self::assertEquals(1, $client->called);
    }

    public function testItReturnsEmptySetWhenNoIssues(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public int $called = 0;

            public function getUrl(string $url): ?array
            {
                $this->called++;

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'total'      => 0,
                    'issues'     => [],
                ];
            }
        };

        $retriever = new JiraSprintIssuesRetrieverFromAPI($client, new NullLogger());

        $issue_ids = $retriever->getArtifactLinkChange(JiraSprint::buildActive(14, 'Sprint 1'));

        self::assertEquals(1, $client->called);
        self::assertCount(0, $issue_ids);
    }

    public function testItReturnsOneIssue(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public int $called = 0;

            public function getUrl(string $url): ?array
            {
                $this->called++;

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'total'      => 1,
                    'issues'     => [
                        [
                            'expand' => 'operations,versionedRepresentations,editmeta,changelog,renderedFields',
                            'id'     => '10000',
                            'self'   => 'https://jira.example.com/rest/agile/1.0/issue/10000',
                            'key'    => 'SP-1',
                        ],
                    ],
                ];
            }
        };

        $retriever = new JiraSprintIssuesRetrieverFromAPI($client, new NullLogger());

        $issue_ids = $retriever->getArtifactLinkChange(JiraSprint::buildActive(14, 'Sprint 1'));

        self::assertEquals(1, $client->called);
        self::assertEquals([new ArtifactLinkChange(10000)], $issue_ids);
    }

    public function testItReturnsIssuesOnSeveralPages(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public int $called = 0;

            public function getUrl(string $url): ?array
            {
                if ($this->called === 0) {
                    $this->called++;
                    return [
                        'maxResults' => 50,
                        'startAt'    => 0,
                        'total'      => 2,
                        'issues'     => [
                            [
                                'expand' => 'operations,versionedRepresentations,editmeta,changelog,renderedFields',
                                'id'     => '10000',
                                'self'   => 'https://jira.example.com/rest/agile/1.0/issue/10000',
                                'key'    => 'SP-1',
                            ],
                        ],
                    ];
                } elseif ($this->called === 1) {
                    assertEquals('/rest/agile/1.0/sprint/14/issue?fields=id&startAt=1', $url);
                    $this->called++;
                    return [
                        'maxResults' => 50,
                        'startAt'    => 1,
                        'total'      => 2,
                        'issues'     => [
                            [
                                'expand' => 'operations,versionedRepresentations,editmeta,changelog,renderedFields',
                                'id'     => '10001',
                                'self'   => 'https://jira.example.com/rest/agile/1.0/issue/10001',
                                'key'    => 'SP-2',
                            ],
                        ],
                    ];
                } else {
                    throw new RuntimeException('Must not happen');
                }
            }
        };

        $retriever = new JiraSprintIssuesRetrieverFromAPI($client, new NullLogger());

        $issue_ids = $retriever->getArtifactLinkChange(JiraSprint::buildActive(14, 'Sprint 1'));

        self::assertEquals(2, $client->called);
        self::assertEquals([new ArtifactLinkChange(10000), new ArtifactLinkChange(10001)], $issue_ids);
    }

    public function testItThrowAnExceptionIfIdIsNotNumeric(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public int $called = 0;

            public function getUrl(string $url): ?array
            {
                $this->called++;

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'total'      => 1,
                    'issues'     => [
                        [
                            'expand' => 'operations,versionedRepresentations,editmeta,changelog,renderedFields',
                            'id'     => '10000ABC',
                            'self'   => 'https://jira.example.com/rest/agile/1.0/issue/10000',
                            'key'    => 'SP-1',
                        ],
                    ],
                ];
            }
        };

        $retriever = new JiraSprintIssuesRetrieverFromAPI($client, new NullLogger());

        $this->expectException(UnexpectedFormatException::class);

        $retriever->getArtifactLinkChange(JiraSprint::buildActive(14, 'Sprint 1'));

        self::assertEquals(1, $client->called);
    }
}
