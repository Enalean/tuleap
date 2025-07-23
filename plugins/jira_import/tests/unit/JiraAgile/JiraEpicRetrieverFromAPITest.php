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
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraEpicRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCallsTheEpicsURL(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            public bool $called = false;
            #[\Override]
            public function getUrl(string $url): ?array
            {
                $this->called = true;
                assertEquals('/rest/agile/latest/board/1/epic?startAt=0', $url);
                return [
                    'isLast' => true,
                    'values' => [],
                ];
            }
        };

        $epic_retriever = new JiraEpicFromBoardRetrieverFromAPI($client, new NullLogger());
        $epic_retriever->getEpics(JiraBoard::buildFakeBoard());

        self::assertTrue($client->called);
    }

    public function testIfBuildEpics(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'isLast' => true,
                    'values' => [
                        [
                            'id'      => 10143,
                            'key'     => 'SP-36',
                            'self'    => 'https://example.com/rest/agile/1.0/epic/10143',
                            'name'    => 'Big Epic',
                            'summary' => 'Some Epic',
                            'color'   => [
                                'key' => 'color_11',
                            ],
                            'done'    => false,
                        ],
                    ],
                ];
            }
        };

        $epic_retriever = new JiraEpicFromBoardRetrieverFromAPI($client, new NullLogger());
        $epics          = $epic_retriever->getEpics(JiraBoard::buildFakeBoard());

        self::assertCount(1, $epics);
        self::assertEquals(10143, $epics[0]->id);
        self::assertEquals('https://example.com/rest/agile/1.0/epic/10143', $epics[0]->url);
        self::assertEquals('SP-36', $epics[0]->key);
    }
}
