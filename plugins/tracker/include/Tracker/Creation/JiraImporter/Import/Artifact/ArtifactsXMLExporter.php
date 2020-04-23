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

    public function __construct(
        ClientWrapper $wrapper,
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        UserManager $user_manager,
        FieldChangeXMLExporter $field_change_xml_exporter
    ) {
        $this->wrapper                   = $wrapper;
        $this->simplexml_cdata_factory   = $simplexml_cdata_factory;
        $this->user_manager              = $user_manager;
        $this->field_change_xml_exporter = $field_change_xml_exporter;
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

        $url = "/search?jql=project=" . urlencode($jira_project_id) . " AND issuetype=" . urlencode($jira_issue_type_name) . "&fields=*all";
        $jira_artifacts_response = $this->wrapper->getUrl($url);

        if (! $jira_artifacts_response) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssuesException();
        }

        $artifacts_node = $tracker_node->addChild('artifacts');

        $this->exportBatchOfIssuesInArtifactXMLFormat(
            $user,
            $artifacts_node,
            $jira_artifacts_response,
            $jira_base_url,
            $jira_field_mapping_collection
        );

        $count_loop = 1;
        $total      = (int) $jira_artifacts_response['total'];
        $is_last    = $total <= (int) $jira_artifacts_response['maxResults'];
        while (! $is_last) {
            $max_results = $jira_artifacts_response['maxResults'];
            $offset      = $jira_artifacts_response['maxResults'] * $count_loop;

            $url = "/search?jql=project=" . urlencode($jira_project_id) . " AND issuetype=" . urlencode($jira_issue_type_name) . "&fields=*all" .
                "&startAt=" . urlencode((string) $offset) . "&maxResults=" . urlencode((string) $max_results);

            $jira_artifacts_response = $this->wrapper->getUrl($url);
            if (! $jira_artifacts_response) {
                throw JiraConnectionException::canNotRetrieveFullCollectionOfIssuesException();
            }

            $this->exportBatchOfIssuesInArtifactXMLFormat(
                $user,
                $artifacts_node,
                $jira_artifacts_response,
                $jira_base_url,
                $jira_field_mapping_collection
            );

            $is_last = (int) $jira_artifacts_response['total'] <=
                ((int) $jira_artifacts_response['startAt'] + (int) $jira_artifacts_response['maxResults']);
            $count_loop++;
        }
    }

    private function exportBatchOfIssuesInArtifactXMLFormat(
        PFUser $user,
        SimpleXMLElement $artifacts_node,
        array $jira_artifacts_response,
        string $jira_base_url,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        if (! isset($jira_artifacts_response['issues'])) {
            return;
        }

        $jira_artifacts = $jira_artifacts_response['issues'];
        if (count($jira_artifacts) === 0) {
            return;
        }

        foreach ($jira_artifacts as $artifact) {
            $artifact_node = $artifacts_node->addChild('artifact');
            $artifact_node->addAttribute('id', $artifact['id']);
            $changeset_node = $artifact_node->addChild('changeset');

            $this->simplexml_cdata_factory->insertWithAttributes(
                $changeset_node,
                'submitted_by',
                $user->getUserName(),
                $format = ['format' => 'username']
            );

            $this->simplexml_cdata_factory->insertWithAttributes(
                $changeset_node,
                'submitted_on',
                date('c', (new \DateTimeImmutable())->getTimestamp()),
                $format = ['format' => 'ISO8601']
            );

            $changeset_node->addChild('comments');

            $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($artifact['key']);
            $field_change_node = $changeset_node->addChild('field_change');
            $field_change_node->addAttribute('type', 'string');
            $field_change_node->addAttribute('field_name', JiraXmlExporter::JIRA_LINK_FIELD_NAME);
            $field_change_node->addChild('value', $jira_link);

            foreach ($artifact['fields'] as $key => $value) {
                $mapping = $jira_field_mapping_collection->getMappingFromJiraField($key);
                if ($mapping !== null && $value !== null) {
                    $this->field_change_xml_exporter->exportFieldChange(
                        $mapping,
                        $changeset_node,
                        $value
                    );
                }
            }
        }
    }
}
