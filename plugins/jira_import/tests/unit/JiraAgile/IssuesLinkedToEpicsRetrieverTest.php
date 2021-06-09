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

use RuntimeException;

final class IssuesLinkedToEpicsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsIssuesLinkedToOneEpic(): void
    {
        $epics_retriever = new class implements JiraEpicRetriever {
            public function getEpics(JiraBoard $board): array
            {
                return [
                    new JiraEpic(10143, 'SP-36', 'whatever'),
                ];
            }
        };

        $epic_issues_retriever = new class implements JiraEpicIssuesRetriever {
            public function getIssueIds(JiraEpic $epic): array
            {
                return ['10005', '10013'];
            }
        };

        $linked_issues_retriever = new IssuesLinkedToEpicsRetriever(
            $epics_retriever,
            $epic_issues_retriever,
        );
        $linked_issues           = $linked_issues_retriever->getLinkedIssues(JiraBoard::buildFakeBoard());

        self::assertEquals(['10005', '10013'], $linked_issues->getChildren('SP-36'));
    }

    public function testItReturnsIssuesLinkedToTwoEpics(): void
    {
        $epics_retriever = new class implements JiraEpicRetriever {
            public function getEpics(JiraBoard $board): array
            {
                return [
                    new JiraEpic(10143, 'SP-36', 'whatever'),
                    new JiraEpic(10144, 'SP-39', 'whatever'),
                ];
            }
        };

        $epic_issues_retriever = new class implements JiraEpicIssuesRetriever {
            public function getIssueIds(JiraEpic $epic): array
            {
                if ($epic->key === 'SP-36') {
                    return ['10005', '10013'];
                }
                if ($epic->key === 'SP-39') {
                    return ['10006'];
                }
                throw new RuntimeException("Must not happen");
            }
        };

        $linked_issues_retriever = new IssuesLinkedToEpicsRetriever(
            $epics_retriever,
            $epic_issues_retriever,
        );
        $linked_issues           = $linked_issues_retriever->getLinkedIssues(JiraBoard::buildFakeBoard());

        self::assertEquals(['10005', '10013'], $linked_issues->getChildren('SP-36'));
        self::assertEquals(['10006'], $linked_issues->getChildren('SP-39'));
    }
}
