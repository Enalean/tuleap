<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use DateTimeImmutable;
use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

class CurrentSnapshotBuilder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CreationStateListValueFormatter
     */
    private $creation_state_list_value_formatter;

    /**
     * @var JiraAuthorRetriever
     */
    private $jira_author_retriever;

    public function __construct(
        LoggerInterface $logger,
        CreationStateListValueFormatter $creation_state_list_value_formatter,
        JiraAuthorRetriever $jira_author_retriever
    ) {
        $this->logger                              = $logger;
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
        $this->jira_author_retriever               = $jira_author_retriever;
    }

    /**
     * @throws JiraConnectionException
     */
    public function buildCurrentSnapshot(
        PFUser $snapshot_owner,
        IssueAPIRepresentation $issue_api_representation,
        FieldMappingCollection $jira_field_mapping_collection
    ): Snapshot {
        $this->logger->debug("Build current snapshot...");

        $field_snapshots = [];
        foreach ($issue_api_representation->getFields() as $key => $value) {
            $rendered_value = $issue_api_representation->getRenderedFieldByKey($key);
            $mapping        = $jira_field_mapping_collection->getMappingFromJiraField($key);

            if ($mapping !== null && $value !== null) {
                $field_value       = $this->getBoundValue($mapping, $value);
                $field_snapshots[] = new FieldSnapshot(
                    $mapping,
                    $field_value,
                    $rendered_value
                );
            }
        }

        $current_snapshot = new Snapshot(
            $snapshot_owner,
            new DateTimeImmutable($issue_api_representation->getFieldByKey(AlwaysThereFieldsExporter::JIRA_UPDATED_ON_NAME)),
            $field_snapshots,
            null
        );

        $this->logger->debug("Current snapshot built successfully");

        return $current_snapshot;
    }

    /**
     * @param mixed $value
     * @return mixed
     * @throws JiraConnectionException
     */
    private function getBoundValue(
        FieldMapping $mapping,
        $value
    ) {
        if (
            $mapping->getBindType() === Tracker_FormElement_Field_List_Bind_Users::TYPE &&
            $mapping->getType() === \Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE
        ) {
            $user = $this->jira_author_retriever->getAssignedTuleapUser(
                $value['accountId']
            );

            $value = $this->creation_state_list_value_formatter->formatListValue(
                (string) $user->getId()
            );
        }

        if (
            $mapping->getBindType() === Tracker_FormElement_Field_List_Bind_Users::TYPE &&
            $mapping->getType() === \Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE
        ) {
            $selected_users_ids = [];

            foreach ($value as $user_representation) {
                $user = $this->jira_author_retriever->getAssignedTuleapUser(
                    $user_representation['accountId']
                );

                if ((int) $user->getId() === (int) TrackerImporterUser::ID) {
                    continue;
                }

                $selected_users_ids[] = $user->getId();
            }

            $value = $this->creation_state_list_value_formatter->formatMultiUserListValues(
                $selected_users_ids
            );
        }

        return $value;
    }
}
