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
use function PHPUnit\Framework\assertEquals;

final class JiraEpicIssuesRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCallsTheEpicsIssueURL(): void
    {
        $client = new class implements JiraClient {
            public bool $called = false;
            public function getUrl(string $url): ?array
            {
                $this->called = true;
                assertEquals('/rest/agile/latest/board/1/epic/10143/issue?fields=id&startAt=0', $url);
                return [
                    'total' => 0,
                    'issues' => [],
                ];
            }
        };

        $epic_retriever = new JiraEpicIssuesRetrieverFromAPI($client, new NullLogger());
        $epic_retriever->getIssueIds(new JiraEpic(10143, '', 'https://example.com/rest/agile/latest/board/1/epic/10143'));

        self::assertTrue($client->called);
    }

    public function testItReturnsTheIssueIds(): void
    {
        $client = new class implements JiraClient {
            public function getUrl(string $url): ?array
            {
                return [
                    'total' => 2,
                    'issues' => [
                        [
                            'expand' => 'operations,versionedRepresentations,editmeta,changelog,renderedFields',
                            'id' => '10005',
                            'self' => 'https://example.com/rest/agile/1.0/issue/10005',
                            'key' => 'SP-6',
                        ],
                        [
                            'expand' => 'operations,versionedRepresentations,editmeta,changelog,renderedFields',
                            'id' => '10013',
                            'self' => 'https://example.com/rest/agile/1.0/issue/10013',
                            'key' => 'SP-24',
                        ],
                    ],
                ];
            }
        };

        $epic_retriever = new JiraEpicIssuesRetrieverFromAPI($client, new NullLogger());
        $ids            = $epic_retriever->getIssueIds(new JiraEpic(10143, '', 'https://example.com/rest/agile/latest/board/1/epic/10143'));

        self::assertEquals(['10005', '10013'], $ids);
    }
}
