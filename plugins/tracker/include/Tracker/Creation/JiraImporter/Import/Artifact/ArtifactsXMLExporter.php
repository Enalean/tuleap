<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

use PFUser;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use UserManager;
use XML_SimpleXMLCDATAFactory;

class ArtifactsXMLExporter
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;

    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simplexml_cdata_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var FieldChangeXMLExporter
     */
    private $field_change_xml_exporter;

    /**
     * @var FieldChangeStringBuilder
     */
    private $field_change_string_builder;

    public function __construct(
        ClientWrapper $wrapper,
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        UserManager $user_manager,
        FieldChangeXMLExporter $field_change_xml_exporter,
        FieldChangeStringBuilder $field_change_string_builder
    ) {
        $this->wrapper                     = $wrapper;
        $this->simplexml_cdata_factory     = $simplexml_cdata_factory;
        $this->user_manager                = $user_manager;
        $this->field_change_xml_exporter   = $field_change_xml_exporter;
        $this->field_change_string_builder = $field_change_string_builder;
    }

    public function exportArtifacts(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $jira_field_mapping_collection,
        string $jira_base_url,
        string $jira_project_id,
        string $jira_issue_type_name
    ): void {
        $user = $this->user_manager->getCurrentUser();
        if ($user === null) {
            return;
        }

        $jira_issues_response = $this->getIssues($jira_project_id, $jira_issue_type_name, null, null);

        $artifacts_node = $tracker_node->addChild('artifacts');

        $this->exportBatchOfIssuesInArtifactXMLFormat(
            $user,
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
                $user,
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

        return '/search?' . http_build_query($params);
    }

    private function exportBatchOfIssuesInArtifactXMLFormat(
        PFUser $user,
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

        foreach ($jira_issues as $issue) {
            $artifact_node = $artifacts_node->addChild('artifact');
            $artifact_node->addAttribute('id', $issue['id']);
            $changeset_node = $artifact_node->addChild('changeset');

            $this->simplexml_cdata_factory->insertWithAttributes(
                $changeset_node,
                'submitted_by',
                $user->getUserName(),
                $format = ['format' => 'username']
            );

            $node_submitted_on = $this->simplexml_cdata_factory->insertWithAttributes(
                $changeset_node,
                'submitted_on',
                date('c', (new \DateTimeImmutable())->getTimestamp()),
                $format = ['format' => 'ISO8601']
            );

            $changeset_node->addChild('comments');

            $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($issue['key']);
            $this->field_change_string_builder->build(
                $changeset_node,
                JiraXmlExporter::JIRA_LINK_FIELD_NAME,
                $jira_link
            );

            foreach ($issue['fields'] as $key => $value) {
                $rendered_value = $issue['renderedFields'][$key] ?? null;
                $mapping        = $jira_field_mapping_collection->getMappingFromJiraField($key);
                if ($mapping !== null && $value !== null) {
                    $this->field_change_xml_exporter->exportFieldChange(
                        $mapping,
                        $changeset_node,
                        $node_submitted_on,
                        $value,
                        $rendered_value
                    );
                }
            }
        }
    }
}
