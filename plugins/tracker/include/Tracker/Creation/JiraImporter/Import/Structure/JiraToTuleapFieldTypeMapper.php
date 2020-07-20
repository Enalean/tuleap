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
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\ErrorCollector;

class JiraToTuleapFieldTypeMapper
{
    /**
     * @var FieldXmlExporter
     */
    private $field_xml_exporter;
    /**
     * @var ErrorCollector
     */
    private $error_collector;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        FieldXmlExporter $field_xml_exporter,
        ErrorCollector $error_collector,
        LoggerInterface $logger
    ) {
        $this->field_xml_exporter = $field_xml_exporter;
        $this->error_collector    = $error_collector;
        $this->logger             = $logger;
    }

    public function exportFieldToXml(
        JiraFieldAPIRepresentation $jira_field,
        ContainersXMLCollection $containers_collection,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        $id               = $jira_field->getId();
        $jira_field_label = $jira_field->getLabel();
        $required         = $jira_field->isRequired();

        // ignore this jira always there mapping who is created like a custom one
        if ($jira_field_label === "Flagged") {
            return;
        }

        if ($jira_field->getSchema() === null) {
            switch ($id) {
                case 'parent':
                case 'statusCategory':
                case 'issuekey':
                case 'thumbnail':
                default:
                    return;
                    break;
            }
        } else {
            $jira_type = $jira_field->getSchema();

            switch ($jira_type) {
                case 'summary':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::LEFT_COLUMN_NAME),
                        Tracker_FormElementFactory::FIELD_STRING_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        AlwaysThereFieldsExporter::JIRA_SUMMARY_RANK,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:textfield':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_STRING_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        1,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case 'description':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::LEFT_COLUMN_NAME),
                        Tracker_FormElementFactory::FIELD_TEXT_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        AlwaysThereFieldsExporter::JIRA_DESCRIPTION_RANK,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:textarea':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_TEXT_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        2,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:float':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        3,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:datepicker':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_DATE_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        4,
                        $required,
                        [
                            'display_time' => '0'
                        ],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:datetime':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_DATE_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        4,
                        $required,
                        [
                            'display_time' => '1'
                        ],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        null
                    );
                    break;
                case AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME:
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
                        Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        AlwaysThereFieldsExporter::JIRA_PRIORITY_RANK,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        \Tracker_FormElement_Field_List_Bind_Static::TYPE
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        5,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        \Tracker_FormElement_Field_List_Bind_Static::TYPE
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiselect':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        6,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        \Tracker_FormElement_Field_List_Bind_Static::TYPE
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:select':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        5,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        \Tracker_FormElement_Field_List_Bind_Static::TYPE
                    );
                    break;
                case AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME:
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
                        Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        AlwaysThereFieldsExporter::JIRA_ASSIGNEE_RANK,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        Tracker_FormElement_Field_List_Bind_Users::TYPE
                    );
                    break;
                case AlwaysThereFieldsExporter::JIRA_REPORTER_NAME:
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
                        Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        AlwaysThereFieldsExporter::JIRA_REPORTER_RANK,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        Tracker_FormElement_Field_List_Bind_Users::TYPE
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:userpicker':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        11,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        Tracker_FormElement_Field_List_Bind_Users::TYPE
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiuserpicker':
                    $this->field_xml_exporter->exportField(
                        $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::CUSTOM_FIELDSET_NAME),
                        Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        12,
                        $required,
                        [],
                        $jira_field->getBoundValues(),
                        $jira_field_mapping_collection,
                        Tracker_FormElement_Field_List_Bind_Users::TYPE
                    );
                    break;
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
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multicheckboxes':
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
                case 'fixVersions':
                case 'security':
                case 'lastViewed':
                case 'com.atlassian.jira.toolkit:LastCommentDate':
                case 'duedate':
                case 'versions':
                case 'com.pyxis.greenhopper.jira:gh-lexo-rank':
                case 'aggregatetimeestimate':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:cascadingselect':
                case 'aggregateprogress':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:url':
                case 'environment':
                case 'resolution':
                case 'components':
                case 'progress':
                case 'com.atlassian.jira.toolkit:assigneedomain':
                case 'com.atlassian.jira.toolkit:reporterdomain':
                case 'com.atlassian.jira.toolkit:lastusercommented':
                case 'com.atlassian.servicedesk.assets-plugin:assetfield':
                case 'com.atlassian.jira.toolkit:userproperty': // ???
                case 'com.atlassian.jira.toolkit:lastupdaterorcommenter':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiversion': //version somthing internal to jira
                case 'com.atlassian.jira.plugin.system.customfieldtypes:version':
                case 'com.atlassian.jira.toolkit:participants':
                case 'com.atlassian.teams:rm-teams-custom-field-team':
                case 'aggregatetimeoriginalestimate':
                case 'com.atlassian.jira.ext.charting:timeinstatus':
                case 'statuscategorychangedate':
                case 'votes':
                case 'project':
                case 'labels':
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
                case 'issuetype':
                    $this->logger->debug(" |_ Field " . $id . " (" . $jira_type . ") ignored ");
                    break;
                default:
                    $this->error_collector->addError("Unknonw mapping type " . $jira_type);
            }
        }
    }
}
