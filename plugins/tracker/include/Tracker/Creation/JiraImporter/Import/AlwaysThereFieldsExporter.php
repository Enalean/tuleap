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

use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\FormElement\Field\ArtifactId\XML\XMLArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\XML\XMLArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\CrossReference\XML\XMLCrossReferenceField;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField;
use Tuleap\Tracker\FormElement\Field\File\XML\XMLFileField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\XML\XMLLastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\XML\XMLSubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\XML\XMLSubmittedOnField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\Field\XML\XMLField;
use Tuleap\Tracker\XML\XMLTracker;

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
    public const JIRA_STORY_POINTS_NAME      = "story_points";
    public const JIRA_ISSUE_TYPE_NAME        = "issuetype";

    public const JIRA_LINK_FIELD_ID        = "jira_issue_url";
    public const JIRA_ARTIFACT_ID_FIELD_ID = "artifact_id";

    public const JIRA_UPDATED_ON_NAME = "updated";
    public const JIRA_CREATED_NAME    = "created";
    public const JIRA_CREATED_BY      = "creator";

    public const JIRA_ISSUE_LINKS_NAME      = 'issuelinks';
    public const JIRA_SUB_TASKS_NAME        = 'subtasks';
    public const JIRA_CROSS_REFERENCES_NAME = 'orgtuleapcrossreferences'; // doesn't exist at jira side

    public const JIRA_RESOLUTION_DATE_NAME = "resolutiondate";
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
    public const JIRA_STORY_POINTS_RANK    = 11;

    public const JIRA_ATTACHMENT_RANK  = 1;
    public const JIRA_SUMMARY_RANK     = 1;
    public const JIRA_DESCRIPTION_RANK = 2;

    public const JIRA_ISSUE_LINKS_RANK      = 1;
    public const JIRA_CROSS_REFERENCES_RANK = 2;

    public const DETAILS_FIELDSET_NAME    = 'details_fieldset';
    public const CUSTOM_FIELDSET_NAME     = 'custom_fieldset';
    public const ATTACHMENT_FIELDSET_NAME = 'attachment_fieldset';
    public const LINKS_FIELDSET_NAME      = 'links_fieldset';

    public const LEFT_COLUMN_NAME  = 'left_column';
    public const RIGHT_COLUMN_NAME = 'right_column';

    public function exportFields(XMLTracker $tracker, StatusValuesCollection $status_values_collection, FieldMappingCollection $field_mapping_collection): XMLTracker
    {
        return $tracker
            ->withFormElement(
                XMLFieldset::fromTrackerAndName($tracker, self::DETAILS_FIELDSET_NAME)
                    ->withRank(1)
                    ->withLabel('Details')
                    ->withFormElements(
                        XMLColumn::fromTrackerAndName($tracker, self::LEFT_COLUMN_NAME)
                            ->withRank(1)
                            ->withLabel(self::LEFT_COLUMN_NAME),
                        XMLColumn::fromTrackerAndName($tracker, self::RIGHT_COLUMN_NAME)
                            ->withRank(2)
                            ->withLabel(self::RIGHT_COLUMN_NAME)
                            ->withFormElements(
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLArtifactIdField::fromTrackerAndName($tracker, self::JIRA_ARTIFACT_ID_FIELD_ID)
                                        ->withLabel('Artifact id')
                                        ->withRank(self::JIRA_ARTIFACT_ID_RANK)
                                        ->withPermissions(... self::getReadOnlyPermissions())
                                ),
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLStringField::fromTrackerAndName($tracker, self::JIRA_LINK_FIELD_NAME)
                                        ->withLabel('Link to original issue')
                                        ->withRank(self::JIRA_LINK_RANK)
                                        ->withPermissions(... self::getReadOnlyPermissions())
                                ),
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLSubmittedByField::fromTrackerAndName($tracker, self::JIRA_CREATED_BY)
                                        ->withLabel('Created by')
                                        ->withRank(self::JIRA_CREATOR_RANK)
                                        ->withPermissions(... self::getSubmitAndUpdatePermissions())
                                ),
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLSubmittedOnField::fromTrackerAndName($tracker, self::JIRA_CREATED_NAME)
                                        ->withLabel('Creation date')
                                        ->withRank(self::JIRA_CREATED_RANK)
                                        ->withPermissions(... self::getSubmitAndUpdatePermissions())
                                ),
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLLastUpdateDateField::fromTrackerAndName($tracker, self::JIRA_UPDATED_ON_NAME)
                                        ->withLabel('Last update date')
                                        ->withRank(self::JIRA_UPDATED_ON_RANK)
                                        ->withPermissions(... self::getSubmitAndUpdatePermissions())
                                ),
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLDateField::fromTrackerAndName($tracker, self::JIRA_RESOLUTION_DATE_NAME)
                                        ->withLabel('Resolved')
                                        ->withDateTime()
                                        ->withRank(self::JIRA_RESOLUTION_DATE_RANK)
                                        ->withPermissions(... self::getSubmitAndUpdatePermissions())
                                ),
                                $this->addToMapping(
                                    $field_mapping_collection,
                                    XMLSelectBoxField::fromTrackerAndName($tracker, self::JIRA_STATUS_NAME)
                                        ->withLabel('Status')
                                        ->withRank(self::JIRA_STATUS_RANK)
                                        ->withStaticValues(
                                            ...array_map(
                                                static fn (JiraFieldAPIAllowedValueRepresentation $value) => new XMLBindStaticValue(
                                                    $value->getXMLId(),
                                                    $value->getName()
                                                ),
                                                $status_values_collection->getAllValues(),
                                            )
                                        )
                                        ->withPermissions(... self::getSubmitAndUpdatePermissions()),
                                    $status_values_collection->getAllValues(),
                                ),
                            ),
                    ),
                XMLFieldset::fromTrackerAndName($tracker, self::CUSTOM_FIELDSET_NAME)
                    ->withRank(2)
                    ->withLabel('Custom Fields'),
                XMLFieldset::fromTrackerAndName($tracker, self::ATTACHMENT_FIELDSET_NAME)
                    ->withRank(3)
                    ->withLabel('Attachments')
                    ->withFormElements(
                        $this->addToMapping(
                            $field_mapping_collection,
                            XMLFileField::fromTrackerAndName($tracker, self::JIRA_ATTACHMENT_NAME)
                                ->withLabel('Attachments')
                                ->withRank(self::JIRA_ATTACHMENT_RANK)
                                ->withPermissions(... self::getSubmitAndUpdatePermissions())
                        ),
                    ),
                XMLFieldset::fromTrackerAndName($tracker, self::LINKS_FIELDSET_NAME)
                    ->withRank(4)
                    ->withLabel('Links')
                    ->withFormElements(
                        $this->addToMapping(
                            $field_mapping_collection,
                            XMLArtifactLinkField::fromTrackerAndName($tracker, self::JIRA_ISSUE_LINKS_NAME)
                                ->withLabel('Links')
                                ->withRank(self::JIRA_ISSUE_LINKS_RANK)
                                ->withPermissions(... self::getSubmitAndUpdatePermissions())
                        ),
                        $this->addToMapping(
                            $field_mapping_collection,
                            XMLCrossReferenceField::fromTrackerAndName($tracker, self::JIRA_CROSS_REFERENCES_NAME)
                                ->withLabel('References')
                                ->withRank(self::JIRA_CROSS_REFERENCES_RANK)
                                ->withPermissions(... self::getSubmitAndUpdatePermissions())
                        ),
                    ),
            );
    }

    private function addToMapping(FieldMappingCollection $field_mapping_collection, XMLField $tuleap_field, array $jira_bound_values = []): XMLField
    {
        $jira_field = new JiraFieldAPIRepresentation(
            $tuleap_field->name,
            $tuleap_field->label,
            false,
            null,
            $jira_bound_values,
            true,
        );
        return $field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $tuleap_field);
    }

    public static function getSubmitAndUpdatePermissions(): array
    {
        return [
            new ReadPermission('UGROUP_ANONYMOUS'),
            new SubmitPermission('UGROUP_REGISTERED'),
            new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
        ];
    }

    public static function getReadOnlyPermissions(): array
    {
        return [
            new ReadPermission('UGROUP_ANONYMOUS'),
        ];
    }

    public static function getUpdateOnlyPermissions(): array
    {
        return [
            new ReadPermission('UGROUP_ANONYMOUS'),
            new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
        ];
    }
}
