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
use function PHPUnit\Framework\assertNotNull;
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

        $project_board_retriever = new JiraAgileImporter(
            $board_retriever,
            $this->getJiraSprintRetrieverWithoutSprints(),
        );

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());
    }

    public function testItHasASprintTracker(): void
    {
        $project_board_retriever = $this->getJiraAgileImport();

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
        $project_board_retriever = $this->getJiraAgileImport();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());

        assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, (string) $xml->trackers->tracker->formElements->formElement[0]['type']);
        assertEquals(1, (int) $xml->trackers->tracker->formElements->formElement[0]['rank']);
        assertEquals('details', (string) $xml->trackers->tracker->formElements->formElement[0]->name);
        assertEquals('Details', (string) $xml->trackers->tracker->formElements->formElement[0]->label);
    }

    public function testSprintTrackerHasNameStringFieldReferencedInColumnsAndCriteria(): void
    {
        $project_board_retriever = $this->getJiraAgileImport();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());

        assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, (string) $xml->trackers->tracker->formElements->formElement[0]['type']);

        assertNotNull($xml->trackers->tracker->formElements->formElement[0]->formElements->formElement[0]);
        $string_field = $xml->trackers->tracker->formElements->formElement[0]->formElements->formElement[0];
        assertEquals('Name', (string) $string_field->label);
        assertEquals($string_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->renderers->renderer[0]->columns->field[0]['REF']);
        assertEquals($string_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->criterias->criteria[0]->field['REF']);

        assertCount(3, $xml->trackers->tracker->permissions->permission);
        assertEquals($string_field['ID'], $xml->trackers->tracker->permissions->permission[0]['REF']);
        assertEquals('PLUGIN_TRACKER_FIELD_READ', $xml->trackers->tracker->permissions->permission[0]['type']);
        assertEquals($string_field['ID'], $xml->trackers->tracker->permissions->permission[1]['REF']);
        assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $xml->trackers->tracker->permissions->permission[1]['type']);
        assertEquals($string_field['ID'], $xml->trackers->tracker->permissions->permission[2]['REF']);
        assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $xml->trackers->tracker->permissions->permission[2]['type']);
    }

    public function testItFetchesSprints(): void
    {
        $board                   = new JiraBoard(1, 'https://example.com', 10000, 'FOO');
        $project_board_retriever = new JiraAgileImporter(
            new class ($board) implements JiraBoardsRetriever
            {
                /**
                 * @var JiraBoard
                 */
                private $fetched_board;

                public function __construct(JiraBoard $fetched_board)
                {
                    $this->fetched_board = $fetched_board;
                }

                public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
                {
                    return $this->fetched_board;
                }
            },
            new class ($board) implements JiraSprintRetriever {
                /**
                 * @var JiraBoard
                 */
                private $fetched_board;

                public function __construct(JiraBoard $fetched_board)
                {
                    $this->fetched_board = $fetched_board;
                }

                public function getAllSprints(JiraBoard $board): array
                {
                    assertSame($this->fetched_board, $board);
                    return [];
                }
            },
        );

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');

        $project_board_retriever->exportScrum(new NullLogger(), $xml, 'FOO', new FieldAndValueIDGenerator());
    }

    private function getJiraAgileImport(): JiraAgileImporter
    {
        return new JiraAgileImporter(
            $this->getJiraBoradRetrieverWithOneBoard(),
            $this->getJiraSprintRetrieverWithoutSprints(),
        );
    }

    private function getJiraBoradRetrieverWithOneBoard(): JiraBoardsRetriever
    {
        return new class implements JiraBoardsRetriever
        {
            public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
            {
                return new JiraBoard(1, 'https://example.com', 10000, 'FOO');
            }
        };
    }

    private function getJiraSprintRetrieverWithoutSprints(): JiraSprintRetriever
    {
        return new class implements JiraSprintRetriever
        {

            public function getAllSprints(JiraBoard $board): array
            {
                return [];
            }
        };
    }
}
