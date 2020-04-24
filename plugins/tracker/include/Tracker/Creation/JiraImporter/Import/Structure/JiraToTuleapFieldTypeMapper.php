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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use SimpleXMLElement;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
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

    public function __construct(
        FieldXmlExporter $field_xml_exporter,
        ErrorCollector $error_collector
    ) {
        $this->field_xml_exporter = $field_xml_exporter;
        $this->error_collector    = $error_collector;
    }

    public function exportFieldToXml(
        array $jira_field,
        string $required,
        SimpleXMLElement $jira_atf_fieldset,
        SimpleXMLElement $jira_custom_fieldset,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        $id               = isset($jira_field['id']) ? $jira_field['id'] : $jira_field['key'];
        $jira_field_label = $jira_field['name'];

        // ignore this jira always there mapping who is created like a custom one
        if ($jira_field_label === "Flagged") {
            return;
        }

        if (! isset($jira_field['schema'])) {
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
            $jira_type = $jira_field['schema']['system'] ?? $jira_field['schema']['custom'];

            switch ($jira_type) {
                case 'summary':
                    $this->field_xml_exporter->exportField(
                        $jira_atf_fieldset,
                        Tracker_FormElement_Field_String::TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        1,
                        $required,
                        $jira_field_mapping_collection
                    );
                    break;
                case 'com.atlassian.jira.plugin.system.customfieldtypes:textfield':
                    $this->field_xml_exporter->exportField(
                        $jira_custom_fieldset,
                        Tracker_FormElement_Field_String::TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        1,
                        $required,
                        $jira_field_mapping_collection
                    );
                    break;
                case 'description':
                    $this->field_xml_exporter->exportField(
                        $jira_atf_fieldset,
                        Tracker_FormElement_Field_Text::TYPE,
                        $id,
                        $jira_field_label,
                        $id,
                        2,
                        $required,
                        $jira_field_mapping_collection
                    );
                    break;
                case 'priority':
                case 'status':
                case 'creator':
                case 'updated':
                case 'created':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:textarea':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:float':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:readonlyfield':
                case 'com.atlassian.jira.toolkit:viewmessage': //view message
                case 'com.atlassian.jira.toolkit:message': //edit message
                case 'com.atlassian.jira.plugin.system.customfieldtypes:datepicker':
                case 'resolutiondate':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:datetime':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multigrouppicker':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:grouppicker':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:radiobuttons':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:select':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:userpicker':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiuserpicker':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multicheckboxes':
                case 'com.atlassian.jira.plugin.system.customfieldtypes:multiselect':
                case 'com.atlassian.jira.toolkit:attachments':
                case 'comment':
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
                case 'reporter':
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
                    return;
                default:
                    $this->error_collector->addError("Unknonw mapping type " . $jira_type);
            }
        }

        $this->error_collector->addError("Unknonw mapping type " . $jira_type);
    }
}
