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

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[CoversClass(JiraSprint::class)]
#[CoversClass(JiraSprintRetrieverFromAPI::class)]
final class JiraSprintRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasNoSprints(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'isLast'     => true,
                    'values'     => [],
                ];
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());
        self::assertEmpty($retriever->getAllSprints(JiraBoard::buildFakeBoard()));
    }

    public function testItHasOneSprint(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'isLast'     => true,
                    'values'     => [
                        [
                            'id'            => 1,
                            'self'          => 'https://example.com/rest/agile/1.0/sprint/1',
                            'state'         => 'active',
                            'name'          => 'Sample Sprint 2',
                            'startDate'     => '2018-01-25T04:04:09.514Z',
                            'endDate'       => '2018-02-08T04:24:09.514Z',
                            'originBoardId' => 1,
                        ],

                    ],
                ];
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());
        $sprints   = $retriever->getAllSprints(JiraBoard::buildFakeBoard());

        self::assertCount(1, $sprints);
        self::assertEquals(1, $sprints[0]->id);
        self::assertEquals('https://example.com/rest/agile/1.0/sprint/1', $sprints[0]->url);
        self::assertEquals('active', $sprints[0]->state);
        self::assertEquals('Sample Sprint 2', $sprints[0]->name);
        self::assertNotNull($sprints[0]->start_date);
        self::assertNotNull($sprints[0]->end_date);
        self::assertEquals('2018-01-25T04:04:09+00:00', $sprints[0]->start_date->format('c'));
        self::assertEquals('2018-02-08T04:24:09+00:00', $sprints[0]->end_date->format('c'));
        self::assertNull($sprints[0]->complete_date);
    }

    public function testItHasSprintsOnSeveralPages(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            private int $call_count = 0;

            public function getUrl(string $url): ?array
            {
                if ($this->call_count === 0) {
                    $this->call_count++;
                    assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);
                    return [
                        'maxResults' => 1,
                        'startAt'    => 0,
                        'isLast'     => false,
                        'values'     => [
                            [
                                'id'            => 1,
                                'self'          => 'https://example.com/rest/agile/1.0/sprint/1',
                                'state'         => 'active',
                                'name'          => 'Sample Sprint 2',
                                'startDate'     => '2018-01-25T04:04:09.514Z',
                                'endDate'       => '2018-02-08T04:24:09.514Z',
                                'originBoardId' => 1,
                            ],

                        ],
                    ];
                } elseif ($this->call_count === 1) {
                    $this->call_count++;
                    assertEquals('/rest/agile/latest/board/1/sprint?startAt=1', $url);

                    return [
                        'maxResults' => 1,
                        'startAt'    => 1,
                        'isLast'     => true,
                        'values'     => [
                            [
                                'id'            => 2,
                                'self'          => 'https://example.com/rest/agile/1.0/sprint/2',
                                'state'         => 'future',
                                'name'          => 'Sample Sprint 3',
                                'originBoardId' => 1,
                            ],

                        ],
                    ];
                } else {
                    throw new \RuntimeException('Should not happen');
                }
            }
        };

        $retriever = new JiraSprintRetrieverFromAPI($client, new NullLogger());
        $sprints   = $retriever->getAllSprints(JiraBoard::buildFakeBoard());

        self::assertCount(2, $sprints);
        self::assertEquals(1, $sprints[0]->id);
        self::assertEquals(2, $sprints[1]->id);
    }

    public function testItHasOneSprintWithUnSupportedState(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            public function getUrl(string $url): ?array
            {
                assertEquals('/rest/agile/latest/board/1/sprint?startAt=0', $url);

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'isLast'     => true,
                    'values'     => [
                        [
                            'id'            => 1,
                            'self'          => 'https://example.com/rest/agile/1.0/sprint/1',
                            'state'         => 'fugu',
                            'name'          => 'Sample Sprint 2',
                            'startDate'     => '2018-01-25T04:04:09.514Z',
                            'endDate'       => '2018-02-08T04:24:09.514Z',
                            'originBoardId' => 1,
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
