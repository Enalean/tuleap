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
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use function PHPUnit\Framework\assertSame;

class JiraBoardsRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasNotExpectedContent(): void
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                return null;
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger());

        $this->expectException(UnexpectedFormatException::class);

        $retriever->getFirstScrumBoardForProject('FOO');
    }

    public function testItHasNoBoard(): void
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                parse_str(parse_url($url, PHP_URL_QUERY), $url_query_parts);
                assertSame('scrum', $url_query_parts['type']);

                return [
                    "maxResults" => 50,
                    "startAt"    => 0,
                    "total"      => 0,
                    "isLast"     => true,
                    "values"     => [],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger());
        self::assertEmpty($retriever->getFirstScrumBoardForProject('FOO'));
    }

    public function testItHasOneBoard(): void
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                return [
                    "maxResults" => 50,
                    "startAt"    => 0,
                    "total"      => 1,
                    "isLast"     => true,
                    "values"     => [
                        [
                            "id"       => 1,
                            "self"     => "https://example.com/rest/agile/1.0/board/1",
                            "name"     => "SP board",
                            "type"     => "scrum",
                            "location" => [
                                "projectId"      => 10000,
                                "displayName"    => "Scrum Project (SP)",
                                "projectName"    => "Scrum Project",
                                "projectKey"     => "SP",
                                "projectTypeKey" => "software",
                                "avatarURI"      => "/secure/projectavatar?size=small&s=small&avatarId=10324",
                                "name"           => "Scrum Project (SP)",
                            ],
                        ],
                    ],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger());
        $board     = $retriever->getFirstScrumBoardForProject('SP');

        self::assertNotNull($board);
        self::assertSame(1, $board->id);
        self::assertSame('https://example.com/rest/agile/1.0/board/1', $board->url);
        self::assertSame('SP', $board->project_key);
        self::assertSame(10000, $board->project_id);
    }

    public function testItHasNoBoardForRequiredProject(): void
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                return [
                    "maxResults" => 50,
                    "startAt"    => 0,
                    "total"      => 1,
                    "isLast"     => true,
                    "values"     => [
                        [
                            "id"       => 1,
                            "self"     => "https://example.com/rest/agile/1.0/board/1",
                            "name"     => "SP board",
                            "type"     => "scrum",
                            "location" => [
                                "projectId"      => 10000,
                                "displayName"    => "Scrum Project (SP)",
                                "projectName"    => "Scrum Project",
                                "projectKey"     => "SP",
                                "projectTypeKey" => "software",
                                "avatarURI"      => "/secure/projectavatar?size=small&s=small&avatarId=10324",
                                "name"           => "Scrum Project (SP)",
                            ],
                        ],
                    ],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger());
        $board     = $retriever->getFirstScrumBoardForProject('BAR');

        self::assertNull($board);
    }

    public function testItGetTheBoardOnTheSecondPage(): void
    {
        $client = new class implements JiraClient
        {
            /**
             * @var int
             */
            private $call_count = 0;

            public function getUrl(string $url): ?array
            {
                if ($this->call_count === 0) {
                    $this->call_count++;
                    return [
                        "maxResults" => 1,
                        "startAt"    => 0,
                        "total"      => 2,
                        "isLast"     => false,
                        "values"     => [
                            [
                                "id"       => 1,
                                "self"     => "https://example.com/rest/agile/1.0/board/1",
                                "name"     => "SP board",
                                "type"     => "scrum",
                                "location" => [
                                    "projectId"      => 10000,
                                    "displayName"    => "Scrum Project (SP)",
                                    "projectName"    => "Scrum Project",
                                    "projectKey"     => "SP",
                                    "projectTypeKey" => "software",
                                    "avatarURI"      => "/secure/projectavatar?size=small&s=small&avatarId=10324",
                                    "name"           => "Scrum Project (SP)",
                                ],
                            ],
                        ],
                    ];
                } elseif ($this->call_count === 1) {
                    $this->call_count++;
                    parse_str(parse_url($url, PHP_URL_QUERY), $url_query_parts);
                    assertSame('1', $url_query_parts['startAt']);

                    return [
                        "maxResults" => 50,
                        "startAt"    => 0,
                        "total"      => 2,
                        "isLast"     => true,
                        "values"     => [
                            [
                                "id"       => 2,
                                "self"     => "https://example.com/rest/agile/1.0/board/2",
                                "name"     => "Bar board",
                                "type"     => "scrum",
                                "location" => [
                                    "projectId"      => 10000,
                                    "displayName"    => "Bar project (BAR)",
                                    "projectName"    => "Bar Project",
                                    "projectKey"     => "BAR",
                                    "projectTypeKey" => "software",
                                    "avatarURI"      => "/secure/projectavatar?size=small&s=small&avatarId=10324",
                                    "name"           => "Scrum Project (SP)",
                                ],
                            ],
                        ],
                    ];
                }
                throw new \RuntimeException('Should not call 3 times');
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger());
        $board     = $retriever->getFirstScrumBoardForProject('BAR');

        self::assertNotNull($board);
        self::assertSame(2, $board->id);
        self::assertSame('BAR', $board->project_key);
    }
}
