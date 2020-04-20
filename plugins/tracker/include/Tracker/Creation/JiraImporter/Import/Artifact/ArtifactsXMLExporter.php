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

use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
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

    public function __construct(
        ClientWrapper $wrapper,
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        UserManager $user_manager
    ) {
        $this->wrapper                 = $wrapper;
        $this->simplexml_cdata_factory = $simplexml_cdata_factory;
        $this->user_manager            = $user_manager;
    }

    public function exportArtifacts(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $jira_field_mapping_collection,
        string $jira_project_id
    ): void {
        $url = "/search?jql=project=" . urlencode($jira_project_id) . "&fields=*all";
        $jira_artifacts_response = $this->wrapper->getUrl($url);

        if (! isset($jira_artifacts_response['issues'])) {
            return;
        }

        $jira_artifacts = $jira_artifacts_response['issues'];
        if (count($jira_artifacts) === 0) {
            return;
        }

        $user = $this->user_manager->getCurrentUser();
        if ($user === null) {
            return;
        }

        $artifacts_node = $tracker_node->addChild('artifacts');
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

            foreach ($artifact['fields'] as $key => $value) {
                $mapping = $jira_field_mapping_collection->getMappingFromJiraField($key);
                if ($mapping !== null) {
                    $field_change_node = $changeset_node->addChild('field_change');
                    $field_change_node->addAttribute('type', 'string');
                    $field_change_node->addAttribute('field_name', $mapping->getFieldName());
                    $field_change_node->addChild('value', $value);
                }
            }
        }
    }
}
