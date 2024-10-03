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

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;

final class IssuesLinkedToEpicsRetriever
{
    public function __construct(
        private JiraEpicFromBoardRetriever $epic_from_board_retriever,
        private JiraEpicFromIssueTypeRetriever $epic_from_issue_type_retriever,
        private JiraEpicIssuesRetriever $epic_issues_retriever,
    ) {
    }

    public function getLinkedIssuesFromBoard(JiraBoard $board, string $jira_project): LinkedIssuesCollection
    {
        $linked_issues_collection = new LinkedIssuesCollection();
        foreach ($this->epic_from_board_retriever->getEpics($board) as $epic) {
            foreach ($this->epic_issues_retriever->getIssueIds($epic, $jira_project) as $issue_id) {
                $linked_issues_collection = $linked_issues_collection->withChild($epic->key, $issue_id);
            }
        }
        return $linked_issues_collection;
    }

    public function getLinkedIssuesFromIssueTypeInProject(IssueType $issue_type, string $jira_project): LinkedIssuesCollection
    {
        $linked_issues_collection = new LinkedIssuesCollection();
        foreach ($this->epic_from_issue_type_retriever->getEpics($issue_type, $jira_project) as $epic) {
            foreach ($this->epic_issues_retriever->getIssueIds($epic, $jira_project) as $issue_id) {
                $linked_issues_collection = $linked_issues_collection->withChild($epic->key, $issue_id);
            }
        }
        return $linked_issues_collection;
    }
}
