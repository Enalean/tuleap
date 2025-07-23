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
use Tuleap\JiraImport\JiraAgile\Board\Projects\JiraBoardProjectsRetriever;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectCollection;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use function PHPUnit\Framework\assertSame;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class JiraBoardsRetrieverFromAPITest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasNotExpectedContent(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return null;
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());

        $this->expectException(UnexpectedFormatException::class);

        $retriever->getFirstScrumBoardForProject('FOO');
    }

    public function testItHasNoBoard(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                parse_str(parse_url($url, PHP_URL_QUERY), $url_query_parts);
                assertSame('scrum', $url_query_parts['type']);

                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'total'      => 0,
                    'isLast'     => true,
                    'values'     => [],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());
        self::assertEmpty($retriever->getFirstScrumBoardForProject('FOO'));
    }

    public function testItHasOneBoard(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'maxResults' => 50,
                    'startAt'    => 0,
                    'total'      => 1,
                    'isLast'     => true,
                    'values'     => [
                        [
                            'id'       => 1,
                            'self'     => 'https://example.com/rest/agile/1.0/board/1',
                            'name'     => 'SP board',
                            'type'     => 'scrum',
                        ],
                    ],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());
        $board     = $retriever->getFirstScrumBoardForProject('SP');

        self::assertNotNull($board);
        self::assertSame(1, $board->id);
        self::assertSame('https://example.com/rest/agile/1.0/board/1', $board->url);
    }

    public function testItRetrievesBoardByIdInProject(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'id'       => 3,
                    'self'     => 'https://example.com/rest/agile/1.0/board/3',
                    'name'     => 'scrum board',
                    'type'     => 'scrum',
                    'location' => [
                        'projectId'   => 10040,
                        'userId'      => 10040,
                        'projectName' => 'Scrum Project',
                        'projectKey'  => 'SP',
                    ],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());
        $board     = $retriever->getScrumBoardByIdForProject('SP', 3);

        self::assertNotNull($board);
        self::assertSame(3, $board->id);
        self::assertSame('https://example.com/rest/agile/1.0/board/3', $board->url);
    }

    public function testItRetrievesBoardByIdInProjectThrowsAnExceptionIfBoardIsNotInProject(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'id'       => 3,
                    'self'     => 'https://example.com/rest/agile/1.0/board/3',
                    'name'     => 'scrum board',
                    'type'     => 'scrum',
                    'location' => [
                        'projectId'   => 10040,
                        'userId'      => 10040,
                        'projectName' => 'Scrum Project',
                        'projectKey'  => 'SP',
                    ],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());

        self::expectException(RuntimeException::class);
        $retriever->getScrumBoardByIdForProject('OtherProject', 3);
    }

    public function testItRetrievesBoardByIdInProjectWithoutLocationKey(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'id'       => 3,
                    'self'     => 'https://example.com/rest/agile/1.0/board/3',
                    'name'     => 'scrum board',
                    'type'     => 'scrum',
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getJiraBoardProjectsRetriever());
        $board     = $retriever->getScrumBoardByIdForProject('SP', 3);

        self::assertNotNull($board);
        self::assertSame(3, $board->id);
        self::assertSame('https://example.com/rest/agile/1.0/board/3', $board->url);
    }

    public function testItRetrievesBoardByIdInProjectWithoutLocationKeyThrowsAnExceptionIfBoardIsNotInProject(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'id'       => 3,
                    'self'     => 'https://example.com/rest/agile/1.0/board/3',
                    'name'     => 'scrum board',
                    'type'     => 'scrum',
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());

        self::expectException(RuntimeException::class);
        $retriever->getScrumBoardByIdForProject('OtherProject', 3);
    }

    public function testItRetrievesBoardByIdInProjectThrowsAnExceptionIfBoardIsNotScrum(): void
    {
        $client = new class extends \Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub
        {
            #[\Override]
            public function getUrl(string $url): ?array
            {
                return [
                    'id'       => 3,
                    'self'     => 'https://example.com/rest/agile/1.0/board/3',
                    'name'     => 'Other type board',
                    'type'     => 'other_type',
                    'location' => [
                        'projectId'   => 10040,
                        'userId'      => 10040,
                        'projectName' => 'Scrum Project',
                        'projectKey'  => 'SP',
                    ],
                ];
            }
        };

        $retriever = new JiraBoardsRetrieverFromAPI($client, new NullLogger(), $this->getEmptyJiraBoardProjectsRetriever());

        self::expectException(RuntimeException::class);
        $retriever->getScrumBoardByIdForProject('SP', 3);
    }

    private function getEmptyJiraBoardProjectsRetriever(): JiraBoardProjectsRetriever
    {
        return new class implements JiraBoardProjectsRetriever
        {
            #[\Override]
            public function getBoardProjects(int $jira_board_id): JiraProjectCollection
            {
                return new JiraProjectCollection();
            }
        };
    }

    private function getJiraBoardProjectsRetriever(): JiraBoardProjectsRetriever
    {
        return new class implements JiraBoardProjectsRetriever
        {
            #[\Override]
            public function getBoardProjects(int $jira_board_id): JiraProjectCollection
            {
                $collection = new JiraProjectCollection();
                $collection->addProject(
                    [
                        'id'    => 'SP',
                        'label' => 'Scrum Project',
                    ]
                );

                return $collection;
            }
        };
    }
}
