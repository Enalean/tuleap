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

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use UserManager;

class ArtifactsXMLExporter
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var DataChangesetXMLExporter
     */
    private $data_changeset_xml_exporter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AttachmentCollectionBuilder
     */
    private $attachment_collection_builder;
    /**
     * @var AttachmentXMLExporter
     */
    private $attachment_xml_exporter;

    public function __construct(
        ClientWrapper $wrapper,
        UserManager $user_manager,
        DataChangesetXMLExporter $data_changeset_xml_exporter,
        AttachmentCollectionBuilder $attachment_collection_builder,
        AttachmentXMLExporter $attachment_xml_exporter,
        LoggerInterface $logger
    ) {
        $this->wrapper                       = $wrapper;
        $this->user_manager                  = $user_manager;
        $this->data_changeset_xml_exporter   = $data_changeset_xml_exporter;
        $this->attachment_collection_builder = $attachment_collection_builder;
        $this->attachment_xml_exporter       = $attachment_xml_exporter;
        $this->logger                        = $logger;
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportArtifacts(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $jira_field_mapping_collection,
        string $jira_base_url,
        string $jira_project_id,
        string $jira_issue_type_name
    ): void {
        $user = $this->user_manager->getUserById(TrackerImporterUser::ID);
        if ($user === null) {
            return;
        }

        $jira_issues_response = $this->getIssues($jira_project_id, $jira_issue_type_name, null, null);

        $artifacts_node = $tracker_node->addChild('artifacts');

        $this->exportBatchOfIssuesInArtifactXMLFormat(
            $artifacts_node,
            $jira_issues_response,
            $jira_base_url,
            $jira_field_mapping_collection
        );

        $count_loop = 1;
        $total      = (int) $jira_issues_response['total'];
        $is_last    = $total <= (int) $jira_issues_response['maxResults'];
        while (! $is_last) {
            $max_results = $jira_issues_response['maxResults'];
            $start_at    = $jira_issues_response['maxResults'] * $count_loop;

            $jira_issues_response = $this->getIssues($jira_project_id, $jira_issue_type_name, $start_at, $max_results);

            $this->exportBatchOfIssuesInArtifactXMLFormat(
                $artifacts_node,
                $jira_issues_response,
                $jira_base_url,
                $jira_field_mapping_collection
            );

            $is_last = (int) $jira_issues_response['total'] <=
                ((int) $jira_issues_response['startAt'] + (int) $jira_issues_response['maxResults']);
            $count_loop++;
        }
    }

    private function getIssues(
        string $jira_project_id,
        string $jira_issue_type_name,
        ?int $start_at,
        ?int $max_results
    ): array {
        $jira_artifacts_response = $this->wrapper->getUrl(
            $this->getUrl($jira_project_id, $jira_issue_type_name, $start_at, $max_results)
        );

        if (! $jira_artifacts_response) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssuesException();
        }

        return $jira_artifacts_response;
    }

    private function getUrl(
        string $jira_project_id,
        string $jira_issue_type_name,
        ?int $start_at,
        ?int $max_results
    ): string {
        $params = [
            'jql'    => 'project=' . $jira_project_id . ' AND issuetype=' . $jira_issue_type_name,
            'fields' => '*all',
            'expand' => 'renderedFields'
        ];

        if ($start_at !== null) {
            $params['startAt'] = $start_at;
        }

        if ($max_results !== null) {
            $params['maxResults'] = $max_results;
        }

        $url = '/search?' . http_build_query($params);
        $this->logger->debug($url);

        return $url;
    }

    /**
     * @throws JiraConnectionException
     */
    private function exportBatchOfIssuesInArtifactXMLFormat(
        SimpleXMLElement $artifacts_node,
        array $jira_issues_response,
        string $jira_base_url,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        if (! isset($jira_issues_response['issues'])) {
            return;
        }

        $jira_issues = $jira_issues_response['issues'];
        if (count($jira_issues) === 0) {
            return;
        }

        $this->logger->debug("Exporting batch of issues.");

        foreach ($jira_issues as $issue) {
            $issue_api_representation = IssueAPIRepresentation::buildFromAPIResponse($issue);

            $issue_id  = $issue_api_representation->getId();
            $issue_key = $issue_api_representation->getKey();

            $this->logger->debug("Exporting issue $issue_key (id: $issue_id)");

            $artifact_node = $artifacts_node->addChild('artifact');
            $artifact_node->addAttribute('id', (string) $issue_id);

            $attachment_collection = $this->attachment_collection_builder->buildCollectionOfAttachment(
                $issue_api_representation
            );

            $this->logger->debug("  |_ Exporting data for issue");
            $this->data_changeset_xml_exporter->exportIssueDataInChangesetXML(
                $artifact_node,
                $jira_field_mapping_collection,
                $issue_api_representation,
                $attachment_collection,
                $jira_base_url
            );

            //Export file info in XML
            $this->logger->debug("  |_ Exporting attachements for issue");
            $this->attachment_xml_exporter->exportCollectionOfAttachmentInXML(
                $attachment_collection,
                $artifact_node
            );
        }
    }
}
