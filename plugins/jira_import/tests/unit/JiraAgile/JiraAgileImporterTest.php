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
use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

class JiraAgileImporterTest extends TestCase
{
    public function testItHasNoBoards(): void
    {
        $board_retriever = new class implements JiraBoardsRetriever
        {
            public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
            {
                assertSame('FOO', $jira_project_key);
                return null;
            }
        };

        $project_board_retriever = new JiraAgileImporter($board_retriever);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());
    }

    public function testItHasASprintTracker(): void
    {
        $board_retriever = new class implements JiraBoardsRetriever
        {
            public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
            {
                return new JiraBoard(1, 'https://example.com', 10000, 'FOO');
            }
        };

        $project_board_retriever = new JiraAgileImporter($board_retriever);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());

        assertCount(1, $xml->trackers->tracker);
        assertEquals('T1', (string) $xml->trackers->tracker[0]['id']);
        assertEquals('Sprints', (string) $xml->trackers->tracker[0]->name);
        assertEquals('sprint', (string) $xml->trackers->tracker[0]->item_name);
        assertEquals('acid-green', (string) $xml->trackers->tracker[0]->color);
    }

    public function testSprintTrackerHasDetailsFieldset(): void
    {
        $board_retriever = new class implements JiraBoardsRetriever
        {
            public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
            {
                return new JiraBoard(1, 'https://example.com', 10000, 'FOO');
            }
        };

        $project_board_retriever = new JiraAgileImporter($board_retriever);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());

        assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, (string) $xml->trackers->tracker->formElements->formElement[0]['type']);
        assertEquals(1, (int) $xml->trackers->tracker->formElements->formElement[0]['rank']);
        assertEquals('details', (string) $xml->trackers->tracker->formElements->formElement[0]->name);
        assertEquals('Details', (string) $xml->trackers->tracker->formElements->formElement[0]->label);
    }
}
