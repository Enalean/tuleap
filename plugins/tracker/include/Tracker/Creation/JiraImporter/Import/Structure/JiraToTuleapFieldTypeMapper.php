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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Psr\Log\LoggerInterface;
use Tuleap\JiraImport\Project\CreateProjectFromJiraCommand;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\ErrorCollector;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField;
use Tuleap\Tracker\FormElement\Field\FloatingPointNumber\XML\XMLFloatField;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLCheckBoxField;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLListField;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLMultiSelectBoxField;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLOpenListField;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLRadioButtonField;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\Text\XML\XMLTextField;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

class JiraToTuleapFieldTypeMapper
{
    public const JIRA_FIELD_VERSIONS               = 'versions';
    public const JIRA_FIELD_FIXEDVERSIONS          = 'fixVersions';
    public const JIRA_FIELD_COMPONENTS             = 'components';
    public const JIRA_FIELD_CUSTOM_MULTIVERSION    = 'com.atlassian.jira.plugin.system.customfieldtypes:multiversion';
    public const JIRA_FIELD_CUSTOM_VERSION         = 'com.atlassian.jira.plugin.system.customfieldtypes:version';
    public const JIRA_FIELD_CUSTOM_MULTICHECKBOXES = 'com.atlassian.jira.plugin.system.customfieldtypes:multicheckboxes';

    public function __construct(
        private ErrorCollector $error_collector,
        private LoggerInterface $logger,
    ) {
    }

    public function exportFieldToXml(
        JiraFieldAPIRepresentation $jira_field,
        XMLTracker $xml_tracker,
        IDGenerator $id_generator,
        PlatformConfiguration $platform_configuration,
        FieldMappingCollection $jira_field_mapping_collection,
        string $import_mode,
    ): XMLTracker {
        $id               = $jira_field->getId();
        $jira_field_label = $jira_field->getLabel();

        // ignore this jira always there mapping who is created like a custom one
        if ($jira_field_label === "Flagged") {
            return $xml_tracker;
        }

        if ($platform_configuration->hasStoryPointsField() && $platform_configuration->getStoryPointsField() === $jira_field->getId()) {
            $this->logger->debug('Field ' . $jira_field->getId() . ' is managed in dedicated converter.');
            return $xml_tracker;
        }

        if ($jira_field->getSchema() === null) {
            switch ($id) {
                case 'parent':
                case 'statusCategory':
                case 'issuekey':
                case 'thumbnail':
                default:
                    return $xml_tracker;
            }
        } else {
            $jira_type = $jira_field->getSchema();

            $permissions = $jira_field->isSubmit() ? AlwaysThereFieldsExporter::getSubmitAndUpdatePermissions() : AlwaysThereFieldsExporter::getUpdateOnlyPermissions();

            switch ($jira_type) {
                case 'summary':
                    $field = XMLStringField::fromTrackerAndName($xml_tracker, AlwaysThereFieldsExporter::JIRA_SUMMARY_FIELD_NAME)
                        ->withLabel($jira_field->getLabel())
                        ->withRank(AlwaysThereFieldsExporter::JIRA_SUMMARY_RANK)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::LEFT_COLUMN_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:textfield':
                    $field = XMLStringField::fromTrackerAndName($xml_tracker, $id)
                        ->withLabel($jira_field->getLabel())
                        ->withRank(1)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'description':
                    $field = XMLTextField::fromTrackerAndName($xml_tracker, AlwaysThereFieldsExporter::JIRA_DESCRIPTION_FIELD_NAME)
                        ->withLabel($jira_field->getLabel())
                        ->withRank(AlwaysThereFieldsExporter::JIRA_DESCRIPTION_RANK)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::LEFT_COLUMN_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:textarea':
                    $field = XMLTextField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(2)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:float':
                    $field = XMLFloatField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(3)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'duedate':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:datepicker':
                    $field = XMLDateField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(4)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:datetime':
                    $field = XMLDateField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(4)
                        ->withRequired($jira_field->isRequired())
                        ->withDateTime()
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME:
                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(AlwaysThereFieldsExporter::JIRA_PRIORITY_RANK)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons':
                    $field = XMLRadioButtonField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(5)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiselect':
                    $field = XMLMultiSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(6)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:select':
                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(5)
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME:
                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(AlwaysThereFieldsExporter::JIRA_ASSIGNEE_RANK)
                        ->withRequired($jira_field->isRequired())
                        ->withUsersValues(
                            new XMLBindUsersValue('group_members')
                        )
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME, $field);

                case AlwaysThereFieldsExporter::JIRA_REPORTER_NAME:
                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(AlwaysThereFieldsExporter::JIRA_REPORTER_RANK)
                        ->withRequired($jira_field->isRequired())
                        ->withUsersValues(
                            new XMLBindUsersValue('group_members')
                        )
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:userpicker':
                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(11)
                        ->withRequired($jira_field->isRequired())
                        ->withUsersValues(
                            new XMLBindUsersValue('group_members')
                        )
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiuserpicker':
                    $field = XMLMultiSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRank(12)
                        ->withRequired($jira_field->isRequired())
                        ->withUsersValues(
                            new XMLBindUsersValue('group_members')
                        )
                        ->withPermissions(... $permissions);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case self::JIRA_FIELD_FIXEDVERSIONS:
                case self::JIRA_FIELD_VERSIONS:
                case self::JIRA_FIELD_COMPONENTS:
                case self::JIRA_FIELD_CUSTOM_MULTIVERSION:
                    $field = XMLMultiSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);
                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case self::JIRA_FIELD_CUSTOM_VERSION:
                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case 'labels':
                    $field = XMLOpenListField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions)
                        ->withBindStatic();

                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case self::JIRA_FIELD_CUSTOM_MULTICHECKBOXES:
                    $field = XMLCheckBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);
                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);

                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::CUSTOM_FIELDSET_NAME, $field);

                case AlwaysThereFieldsExporter::JIRA_ISSUE_TYPE_NAME:
                    if ($import_mode !== CreateProjectFromJiraCommand::OPT_IMPORT_MODE_MONO_TRACKER_VALUE) {
                        break;
                    }

                    $field = XMLSelectBoxField::fromTrackerAndName($xml_tracker, $jira_field->getId())
                        ->withLabel($jira_field->getLabel())
                        ->withRequired($jira_field->isRequired())
                        ->withPermissions(... $permissions);
                    $field = $this->addBoundStaticValues($field, $jira_field);
                    $jira_field_mapping_collection->addMappingBetweenTuleapAndJiraField($jira_field, $field);
                    return $xml_tracker->appendFormElement(AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME, $field);

                case 'attachment':
                case 'status':
                case 'creator':
                case 'created':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:readonlyfield':
                case 'com.atlassian.jira.toolkit:viewmessage': //view message
                case 'com.atlassian.jira.toolkit:message': //edit message
                case 'resolutiondate': // this field is not always displayed in issue view, always created.
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multigrouppicker':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:grouppicker':
                case 'com.atlassian.jira.toolkit:attachments':
                case 'comment':
                case 'updated': // this is not a Tuleap field, handled during artifact import.
                case 'com.pyxis.greenhopper.jira:jsw-issue-color':
                case 'com.pyxis.greenhopper.jira:gh-epic-color':
                case 'aggregatetimespent':
                case 'timespent':
                case 'timetracking':
                case 'worklog':
                case 'com.pyxis.greenhopper.jira:gh-epic-link':
                case 'com.atlassian.jpo:jpo-custom-mapping-parent':
                case 'com.pyxis.greenhopper.jira:gh-sprint':
                case 'typeparent':
                case 'subtasks':
                case 'parent':
                case 'issuelinks':
                case 'timeestimate':
                case 'com.pyxis.greenhopper.jira:jsw-story-points':
                case 'timeoriginalestimate':
                case 'com.atlassian.jira.plugins.jira-development-integration-plugin:devsummarycf':
                case 'security':
                case 'lastViewed':
                case 'com.atlassian.jira.toolkit:LastCommentDate':
                case 'com.pyxis.greenhopper.jira:gh-lexo-rank':
                case 'aggregatetimeestimate':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:cascadingselect':
                case 'aggregateprogress':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:url':
                case 'environment':
                case 'resolution':
                case 'progress':
                case 'com.atlassian.jira.toolkit:assigneedomain':
                case 'com.atlassian.jira.toolkit:reporterdomain':
                case 'com.atlassian.jira.toolkit:lastusercommented':
                case 'com.atlassian.servicedesk.assets-plugin:assetfield':
                case 'com.atlassian.jira.toolkit:userproperty': // ???
                case 'com.atlassian.jira.toolkit:lastupdaterorcommenter':
                case 'com.atlassian.jira.toolkit:participants':
                case 'com.atlassian.teams:rm-teams-custom-field-team':
                case 'aggregatetimeoriginalestimate':
                case 'com.atlassian.jira.ext.charting:timeinstatus':
                case 'statuscategorychangedate':
                case 'votes':
                case 'project':
                case 'watches':
                case 'workratio':
                case 'com.atlassian.servicedesk:sd-customer-organizations':
                case 'com.atlassian.servicedesk:vp-origin':
                case 'com.atlassian.servicedesk:sd-request-participants':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:labels':
                case 'com.atlassian.jpo:jpo-custom-field-parent':
                case 'com.atlassian.jira.ext.charting:firstresponsedate':
                case 'com.atlassian.jira.toolkit:dayslastcommented':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:project':
                case 'com.atlassian.jira.toolkit:comments':
                case 'com.pyxis.greenhopper.jira:gh-epic-label':
                case 'com.pyxis.greenhopper.jira:gh-epic-status':
                case 'issuerestriction':
                    $this->logger->debug(" |_ Field " . $id . " (" . $jira_type . ") ignored ");
                    break;
                default:
                    $this->error_collector->addError("Unknown mapping type " . $jira_type);
            }
        }

        return $xml_tracker;
    }

    private function addBoundStaticValues(XMLListField $tuleap_field, JiraFieldAPIRepresentation $jira_field): XMLListField
    {
        return $tuleap_field->withStaticValues(
            ...array_map(
                static fn (JiraFieldAPIAllowedValueRepresentation $value) => XMLBindStaticValue::fromLabel($tuleap_field, $value->getName()),
                $jira_field->getBoundValues()
            )
        );
    }
}
