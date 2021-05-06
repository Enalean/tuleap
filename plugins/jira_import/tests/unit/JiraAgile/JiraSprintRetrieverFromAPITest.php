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

namespace Tuleap\JiraImport\JiraAgile;

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

/**
 * @covers \Tuleap\JiraImport\JiraAgile\JiraSprint
 * @covers \Tuleap\JiraImport\JiraAgile\JiraSprintRetrieverFromAPI
 */
final class JiraSprintRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasNoSprints()
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);

                return [
                    "maxResults" => 50,
                    "startAt"    => 0,
                    "isLast"     => true,
                    "values"     => [],
                ];
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());
        assertEmpty($retriever->getAllSprints(JiraBoard::buildFakeBoard()));
    }

    public function testItHasOneSprint(): void
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);

                return [
                    "maxResults" => 50,
                    "startAt"    => 0,
                    "isLast"     => true,
                    "values"     => [
                        [
                            "id"            => 1,
                            "self"          => "https://example.com/rest/agile/1.0/sprint/1",
                            "state"         => "active",
                            "name"          => "Sample Sprint 2",
                            "startDate"     => "2018-01-25T04:04:09.514Z",
                            "endDate"       => "2018-02-08T04:24:09.514Z",
                            "originBoardId" => 1,
                        ],

                    ],
                ];
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());
        $sprints   = $retriever->getAllSprints(JiraBoard::buildFakeBoard());

        assertCount(1, $sprints);
        assertEquals(1, $sprints[0]->id);
        assertEquals('https://example.com/rest/agile/1.0/sprint/1', $sprints[0]->url);
        assertEquals('active', $sprints[0]->state);
        assertEquals('Sample Sprint 2', $sprints[0]->name);
        assertEquals('2018-01-25T04:04:09+00:00', $sprints[0]->start_date->format('c'));
        assertEquals('2018-02-08T04:24:09+00:00', $sprints[0]->end_date->format('c'));
        assertNull($sprints[0]->complete_date);
    }

    public function testItHasSprintsOnSeveralPages(): void
    {
        $client = new class implements JiraClient
        {
            private $call_count = 0;

            public function getUrl(string $url): ?array
            {
                if ($this->call_count === 0) {
                    $this->call_count++;
                    assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);
                    return [
                        "maxResults" => 1,
                        "startAt"    => 0,
                        "isLast"     => false,
                        "values"     => [
                            [
                                "id"            => 1,
                                "self"          => "https://example.com/rest/agile/1.0/sprint/1",
                                "state"         => "active",
                                "name"          => "Sample Sprint 2",
                                "startDate"     => "2018-01-25T04:04:09.514Z",
                                "endDate"       => "2018-02-08T04:24:09.514Z",
                                "originBoardId" => 1,
                            ],

                        ],
                    ];
                }
                if ($this->call_count === 1) {
                    $this->call_count++;
                    assertEquals('/rest/agile/latest/board/1/sprint?startAt=1', $url);

                    return [
                        "maxResults" => 1,
                        "startAt"    => 1,
                        "isLast"     => true,
                        "values"     => [
                            [
                                "id"            => 2,
                                "self"          => "https://example.com/rest/agile/1.0/sprint/2",
                                "state"         => "future",
                                "name"          => "Sample Sprint 3",
                                "originBoardId" => 1,
                            ],

                        ],
                    ];
                }
                if ($this->call_count > 1) {
                    throw new \RuntimeException("Should not happen");
                }
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());
        $sprints   = $retriever->getAllSprints(JiraBoard::buildFakeBoard());

        assertCount(2, $sprints);
        assertEquals(1, $sprints[0]->id);
        assertEquals(2, $sprints[1]->id);
    }

    public function testItHasOneSprintWithUnSupportedState(): void
    {
        $client = new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);

                return [
                    "maxResults" => 50,
                    "startAt"    => 0,
                    "isLast"     => true,
                    "values"     => [
                        [
                            "id"            => 1,
                            "self"          => "https://example.com/rest/agile/1.0/sprint/1",
                            "state"         => "fugu",
                            "name"          => "Sample Sprint 2",
                            "startDate"     => "2018-01-25T04:04:09.514Z",
                            "endDate"       => "2018-02-08T04:24:09.514Z",
                            "originBoardId" => 1,
                        ],

                    ],
                ];
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());

        $this->expectException(UnexpectedFormatException::class);

        $retriever->getAllSprints(JiraBoard::buildFakeBoard());
    }
}
