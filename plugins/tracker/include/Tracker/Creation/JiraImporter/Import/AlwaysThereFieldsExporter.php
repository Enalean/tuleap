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

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;

class AlwaysThereFieldsExporter
{
    public const JIRA_LINK_FIELD_NAME        = "jira_issue_url";
    public const JIRA_SUMMARY_FIELD_NAME     = "summary";
    public const JIRA_DESCRIPTION_FIELD_NAME = "description";
    public const JIRA_STATUS_NAME            = "status";
    public const JIRA_PRIORITY_NAME          = "priority";
    public const JIRA_ATTACHMENT_NAME        = "attachment";
    public const JIRA_ASSIGNEE_NAME          = "assignee";
    public const JIRA_REPORTER_NAME          = "reporter";

    public const JIRA_LINK_FIELD_ID        = "jira_issue_url";
    public const JIRA_ARTIFACT_ID_FIELD_ID = "artifact_id";

    public const JIRA_UPDATED_ON_NAME        = "updated";
    public const JIRA_CREATED_NAME           = "created";
    public const JIRA_CREATED_BY             = "creator";

    private const JIRA_RESOLUTION_DATE_NAME  = "resolutiondate";
    public const JIRA_STATUS_RANK          = 1;
    public const JIRA_CREATOR_RANK         = 2;
    public const JIRA_CREATED_RANK         = 3;
    public const JIRA_UPDATED_ON_RANK      = 4;
    public const JIRA_RESOLUTION_DATE_RANK = 5;
    public const JIRA_PRIORITY_RANK        = 6;
    public const JIRA_ARTIFACT_ID_RANK     = 7;
    public const JIRA_LINK_RANK            = 8;
    public const JIRA_ASSIGNEE_RANK        = 9;
    public const JIRA_REPORTER_RANK        = 10;

    public const JIRA_ATTACHMENT_RANK  = 1;
    public const JIRA_SUMMARY_RANK     = 1;
    public const JIRA_DESCRIPTION_RANK = 2;

    /**
     * @var FieldXmlExporter
     */
    private $field_xml_exporter;

    public function __construct(FieldXmlExporter $field_xml_exporter)
    {
        $this->field_xml_exporter = $field_xml_exporter;
    }

    public function exportFields(
        ContainersXMLCollection $containers_collection,
        FieldMappingCollection $field_mapping_collection,
        StatusValuesCollection $status_values_collection
    ): void {
        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
            self::JIRA_ARTIFACT_ID_FIELD_ID,
            "Artifact id",
            self::JIRA_ARTIFACT_ID_FIELD_ID,
            self::JIRA_ARTIFACT_ID_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
            self::JIRA_LINK_FIELD_NAME,
            "Link to original issue",
            self::JIRA_LINK_FIELD_ID,
            self::JIRA_LINK_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE,
            self::JIRA_CREATED_BY,
            "Created by",
            self::JIRA_CREATED_BY,
            self::JIRA_CREATOR_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE,
            self::JIRA_CREATED_NAME,
            "Creation date",
            self::JIRA_CREATED_NAME,
            self::JIRA_CREATED_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE,
            self::JIRA_UPDATED_ON_NAME,
            "Last update date",
            self::JIRA_UPDATED_ON_NAME,
            self::JIRA_UPDATED_ON_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_DATE_TYPE,
            self::JIRA_RESOLUTION_DATE_NAME,
            "Resolved",
            self::JIRA_RESOLUTION_DATE_NAME,
            self::JIRA_RESOLUTION_DATE_RANK,
            false,
            [
                'display_time' => '1'
            ],
            [],
            $field_mapping_collection,
            null
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            self::JIRA_STATUS_NAME,
            "Status",
            self::JIRA_STATUS_NAME,
            self::JIRA_STATUS_RANK,
            false,
            [],
            $status_values_collection->getAllValues(),
            $field_mapping_collection,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::ATTACHMENT_FIELDSET_NAME),
            Tracker_FormElementFactory::FIELD_FILE_TYPE,
            self::JIRA_ATTACHMENT_NAME,
            "Attachments",
            self::JIRA_ATTACHMENT_NAME,
            self::JIRA_ATTACHMENT_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null
        );
    }
}
