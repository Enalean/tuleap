<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use LogicException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use UserManager;

class ArtifactsInDedicatedTrackerXMLExporter
{
    public function __construct(
        private readonly JiraClient $jira_client,
        private readonly UserManager $user_manager,
        private readonly LoggerInterface $logger,
        private readonly IssueAsArtifactXMLExporter $issue_as_artifact_xml_exporter,
    ) {
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportArtifacts(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $jira_field_mapping_collection,
        IssueAPIRepresentationCollection $issue_representation_collection,
        LinkedIssuesCollection $linked_issues_collection,
        string $jira_base_url,
        string $jira_project_id,
        string $jira_issue_type_name,
    ): void {
        $user = $this->user_manager->getUserById(TrackerImporterUser::ID);
        if ($user === null) {
            return;
        }

        $artifacts_node = $tracker_node->addChild('artifacts');
        if ($artifacts_node === null) {
            throw new LogicException('must not be here.');
        }

        $already_seen_artifacts_ids = [];

        foreach ($this->getIterator($jira_project_id, $jira_issue_type_name) as $issue) {
            $this->issue_as_artifact_xml_exporter->exportIssueInArtifactXMLFormat(
                $artifacts_node,
                $issue,
                $jira_base_url,
                $jira_field_mapping_collection,
                $issue_representation_collection,
                $linked_issues_collection,
                $already_seen_artifacts_ids,
            );
        }
    }

    private function getIterator(string $jira_project_id, string $jira_issue_type_name): \Generator
    {
        if ($this->jira_client->isJiraCloud()) {
            return JiraCollectionBuilder::iterateUntilIsLast(
                $this->jira_client,
                $this->logger,
                ClientWrapper::JIRA_CLOUD_JQL_SEARCH_URL . '?' . $this->getUrl($jira_project_id, $jira_issue_type_name),
                'issues',
            );
        }
        return JiraCollectionBuilder::iterateUntilTotal(
            $this->jira_client,
            $this->logger,
            ClientWrapper::JIRA_CORE_BASE_URL . '/search?' . $this->getUrl($jira_project_id, $jira_issue_type_name),
            'issues',
        );
    }

    private function getUrl(
        string $jira_project_id,
        string $jira_issue_type_id,
    ): string {
        $params = [
            'jql'    => sprintf('project="%s" AND issuetype=%s ORDER BY created ASC', $jira_project_id, $jira_issue_type_id),
            'fields' => '*all',
            'expand' => 'renderedFields',
        ];

        return http_build_query($params);
    }
}
