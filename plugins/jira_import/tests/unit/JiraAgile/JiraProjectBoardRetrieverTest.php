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

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

class JiraProjectBoardRetrieverTest extends TestCase
{
    public function testItHasNoBoards(): void
    {
        $board_retriever = new class ($this) implements JiraBoardsRetriever
        {
            public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
            {
                assertSame('FOO', $jira_project_key);
                return null;
            }
        };

        $project_board_retriever = new JiraProjectBoardRetriever($board_retriever);

        self::assertFalse($project_board_retriever->hasScrum('FOO'));
    }

    public function testItHasOneBoard(): void
    {
        $board_retriever = new class implements JiraBoardsRetriever
        {
            public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
            {
                return new JiraBoard(1, 'https://example.com', 10000, 'FOO');
            }
        };

        $project_board_retriever = new JiraProjectBoardRetriever($board_retriever);

        self::assertTrue($project_board_retriever->hasScrum('FOO'));
    }
}
