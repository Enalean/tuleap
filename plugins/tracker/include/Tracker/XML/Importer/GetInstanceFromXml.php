<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Tracker\XML\Importer;

use Exception;
use Project;
use SimpleXMLElement;
use Tracker_CannedResponseFactory;
use Tracker_Exception;
use Tracker_FormElement;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use Tracker_RuleFactory;
use TrackerFactory;
use Tuleap\Color\ItemColor;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Tracker\Semantic\TrackerSemanticFactory;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use User\XML\Import\IFindUserFromXMLReference;
use WorkflowFactory;

readonly class GetInstanceFromXml
{
    public function __construct(
        private TrackerFactory $tracker_factory,
        private Tracker_CannedResponseFactory $canned_response_factory,
        private Tracker_FormElementFactory $formelement_factory,
        private IFindUserFromXMLReference $user_finder,
        private TrackerXmlImportFeedbackCollector $feedback_collector,
        private TrackerSemanticFactory $semantic_factory,
        private Tracker_RuleFactory $rule_factory,
        private Tracker_ReportFactory $report_factory,
        private WorkflowFactory $workflow_factory,
        private WebhookFactory $webhook_factory,
        private UGroupRetrieverWithLegacy $ugroup_retriever_with_legacy,
        private \Psr\Log\LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Tracker_Exception
     */
    public function getInstanceFromXML(
        SimpleXMLElement $xml,
        Project $project,
        string $name,
        string $description,
        string $itemname,
        ?string $color,
        array $created_trackers_mapping,
        array &$xml_fields_mapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
    ): Tracker {
        $row     = $this->setTrackerGeneralInformation($xml, $project, $name, $description, $itemname, $color);
        $tracker = $this->tracker_factory->getInstanceFromRow($row);

        $this->setCannedResponses($xml, $tracker);
        $this->setFormElementFields($xml, $tracker, $xml_fields_mapping);
        $this->setSemantics($xml, $tracker, $created_trackers_mapping, $xml_fields_mapping);

        /*
         * Legacy compatibility
         *
         * All new Tuleap versions will not export dependencies but rules instead.
         * However, we still want to be able to import old xml files.
         *
         * SimpleXML does not allow for nodes to be moved so have to recursively
         * generate rules from the dependencies data.
         */
        $this->setLegacyDependencies($xml);

        $this->setRules($xml, $tracker, $xml_fields_mapping);
        $this->setTrackerReports($xml, $project, $tracker, $xml_fields_mapping, $reports_xml_mapping, $renderers_xml_mapping);
        $this->setWorkflow($xml, $project, $tracker, $xml_fields_mapping);
        $this->setWebhooks($xml, $tracker);
        $this->setPermissions($xml, $project, $tracker, $xml_fields_mapping);

        $this->checkPermissions($tracker, $xml_fields_mapping);

        return $tracker;
    }

    /**
     * protected for testing purpose
     */
    protected function setTrackerGeneralInformation(
        SimpleXMLElement $xml,
        Project $project,
        string $name,
        string $description,
        string $itemname,
        ?string $color,
    ): array {
        $xml_tracker_color_name = $color ?? (string) $xml->color;
        if ($xml_tracker_color_name === '') {
            $tracker_color = ItemColor::default();
        } else {
            $tracker_color = ItemColor::fromNotStandardizedName($xml_tracker_color_name);
        }

        $att = $xml->attributes();
        if ($att === null) {
            throw new Exception('Unable to get the xml attributes of the tracker');
        }

        $row                                 = [
            'id'                  => 0,
            'name'                => $name,
            'group_id'            => (int) $project->getId(),
            'description'         => $description,
            'item_name'           => $itemname,
            'submit_instructions' => (string) $xml->submit_instructions,
            'browse_instructions' => (string) $xml->browse_instructions,
            'status'              => '',
            'deletion_date'       => '',
            'color'               => $tracker_color->getName(),
        ];
        $row['allow_copy']                   = isset($att['allow_copy']) ?
            (int) $att['allow_copy'] : 1;
        $row['enable_emailgateway']          = isset($att['enable_emailgateway']) ?
            (int) $att['enable_emailgateway'] : 0;
        $row['instantiate_for_new_projects'] = isset($att['instantiate_for_new_projects']) ?
            (int) $att['instantiate_for_new_projects'] : 0;
        $row['log_priority_changes']         = isset($att['log_priority_changes']) ?
            (int) $att['log_priority_changes'] : 0;
        $row['notifications_level']          = $this->getNotificationsLevel($att);

        return $row;
    }

    /**
     * protected for testing purpose
     */
    protected function setCannedResponses(SimpleXMLElement $xml, Tracker $tracker): void
    {
        if (! $xml->cannedResponses) {
            return;
        }
        foreach ($xml->cannedResponses->cannedResponse as $index => $response) {
            $tracker->cannedResponses[] = $this->canned_response_factory->getInstanceFromXML($response);
        }
    }

    /**
     * protected for testing purpose
     */
    protected function setFormElementFields(
        SimpleXMLElement $xml,
        Tracker $tracker,
        array &$xml_fields_mapping,
    ): void {
        $elements = $this->getFormElementsFromXml($xml);

        foreach ($elements as $elem) {
            $form_element = $this->formelement_factory->getInstanceFromXML(
                $tracker,
                $elem,
                $xml_fields_mapping,
                $this->user_finder,
                $this->feedback_collector
            );

            if (! $form_element) {
                continue;
            }

            $tracker->formElements[] = $form_element;
        }
    }

    /**
     * protected for testing purpose
     */
    protected function setSemantics(
        SimpleXMLElement $xml,
        Tracker $tracker,
        array $created_trackers_mapping,
        array $xml_fields_mapping,
    ): void {
        if (! $xml->semantics) {
            return;
        }
        foreach ($xml->semantics->semantic as $xml_semantic) {
            $semantic = $this->semantic_factory->getInstanceFromXML(
                $xml_semantic,
                $xml->semantics,
                $xml_fields_mapping,
                $tracker,
                $created_trackers_mapping
            );

            if ($semantic) {
                $tracker->semantics[] = $semantic;
            }
        }
    }

    /**
     * protected for testing purpose
     */
    protected function setLegacyDependencies(SimpleXMLElement $xml): void
    {
        $dependencies = $xml->dependencies;
        if (! $dependencies) {
            return;
        }

        if (! $xml->rules) {
            $rules = $xml->addChild('rules');
            if ($rules === null) {
                throw new Exception('Unable to create <rules> element');
            }
            $list_rules = $rules->addChild('list_rules');
        } elseif (! $xml->rules->list_rules) {
            $list_rules = $xml->rules->addChild('list_rules');
        } else {
            $list_rules = $xml->rules->list_rules;
        }

        if ($list_rules === null) {
            return;
        }

        foreach ($dependencies->rule as $old_rule) {
            $source_field_attributes = $old_rule->source_field->attributes();
            $target_field_attributes = $old_rule->target_field->attributes();
            $source_value_attributes = $old_rule->source_value->attributes();
            $target_value_attributes = $old_rule->target_value->attributes();
            if ($source_field_attributes === null || $target_field_attributes === null || $source_value_attributes === null || $target_value_attributes === null) {
                continue;
            }

            $new_rule = $list_rules->addChild('rule');
            if ($new_rule === null) {
                throw new Exception('Unable to create <rule> element');
            }

            $source_field = $new_rule->addChild('source_field');
            if ($source_field === null) {
                throw new Exception('Unable to create <source_field> element');
            }
            $source_field->addAttribute('REF', (string) $source_field_attributes['REF']);

            $target_field = $new_rule->addChild('target_field');
            if ($target_field === null) {
                throw new Exception('Unable to create <target_field> element');
            }
            $target_field->addAttribute('REF', (string) $target_field_attributes['REF']);

            $source_value = $new_rule->addChild('source_value');
            if ($source_value === null) {
                throw new Exception('Unable to create <source_value> element');
            }
            $source_value->addAttribute('REF', (string) $source_value_attributes['REF']);

            $target_value = $new_rule->addChild('target_value');
            if ($target_value === null) {
                throw new Exception('Unable to create <target_value> element');
            }
            $target_value->addAttribute('REF', (string) $target_value_attributes['REF']);
        }
    }

    private function checkPermissions(Tracker $tracker, array $xml_fields_mapping): void
    {
        foreach ($tracker->getFormElementFields() as $field) {
            if ($field instanceof Tracker_FormElement_Field_ReadOnly) {
                continue;
            }

            if (! $field->hasCachedPermissions()) {
                $xml_id = $this->getXMLReference($field, $xml_fields_mapping);

                $this->feedback_collector
                    ->addWarnings(
                        sprintf(
                            dgettext('tuleap-tracker', 'Tracker %s : field %s (%s) has no permission'),
                            $tracker->getName(),
                            $field->getName(),
                            $xml_id
                        )
                    );
            }
        }
    }

    /**
     * protected for testing purpose
     */
    protected function setRules(SimpleXMLElement $xml, Tracker $tracker, array &$xml_fields_mapping): void
    {
        if (! $xml->rules) {
            return;
        }
        $tracker->rules = $this->rule_factory->getInstanceFromXML($xml->rules, $xml_fields_mapping, $tracker);
    }

    /**
     * protected for testing purpose
     */
    protected function setTrackerReports(
        SimpleXMLElement $xml,
        Project $project,
        Tracker $tracker,
        array &$xml_fields_mapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
    ): void {
        if (! $xml->reports) {
            return;
        }
        foreach ($xml->reports->report as $report) {
            $tracker->reports[] = $this->report_factory->getInstanceFromXML(
                $report,
                $xml_fields_mapping,
                $reports_xml_mapping,
                $renderers_xml_mapping,
                (int) $project->getId()
            );
        }
    }

    /**
     * protected for testing purpose
     */
    protected function setWorkflow(
        SimpleXMLElement $xml,
        Project $project,
        Tracker $tracker,
        array &$xml_fields_mapping,
    ): void {
        if (isset($xml->workflow->field_id)) {
            $tracker->setWorkflow($this->workflow_factory->getInstanceFromXML(
                $xml->workflow,
                $xml_fields_mapping,
                $tracker,
                $project
            ));
        } elseif (isset($xml->simple_workflow->field_id)) {
            $tracker->setWorkflow($this->workflow_factory->getSimpleInstanceFromXML(
                $xml->simple_workflow,
                $xml_fields_mapping,
                $tracker,
                $project
            ));
        }
    }

    /**
     * protected for testing purpose
     */
    protected function setWebhooks(SimpleXMLElement $xml, Tracker $tracker): void
    {
        if (! $xml->webhooks) {
            return;
        }
        $tracker->webhooks = $this->webhook_factory->getWebhooksFromXML($xml->webhooks);
    }

    /**
     * protected for testing purpose
     */
    protected function setPermissions(
        SimpleXMLElement $xml,
        Project $project,
        Tracker $tracker,
        array $xml_mapping,
    ): void {
        if (! $xml->permissions->permission) {
            return;
        }
        $allowed_tracker_perms = [
            Tracker::PERMISSION_ADMIN,
            Tracker::PERMISSION_FULL,
            Tracker::PERMISSION_SUBMITTER,
            Tracker::PERMISSION_ASSIGNEE,
            Tracker::PERMISSION_SUBMITTER_ONLY,
        ];
        $allowed_field_perms   = [
            'PLUGIN_TRACKER_FIELD_READ',
            'PLUGIN_TRACKER_FIELD_UPDATE',
            'PLUGIN_TRACKER_FIELD_SUBMIT',
        ];

        foreach ($xml->permissions->permission as $permission) {
            $ugroup_name = (string) $permission['ugroup'];
            $ugroup_id   = $this->ugroup_retriever_with_legacy->getUGroupId($project, $ugroup_name);
            if (is_null($ugroup_id)) {
                $this->logger->error(
                    "Custom ugroup '$ugroup_name' does not seem to exist for '{$project->getPublicName()}' project."
                );
                continue;
            }
            $type = (string) $permission['type'];

            switch ((string) $permission['scope']) {
                case 'tracker':
                    //tracker permissions
                    if (! in_array($type, $allowed_tracker_perms, true)) {
                        $this->logger->error("Can not import permission of type $type for tracker.");
                        break;
                    }
                    $this->logger->debug(
                        "Adding '$type' permission to '$ugroup_name' on tracker '{$tracker->getName()}'."
                    );
                    $tracker->setCachePermission($ugroup_id, $type);
                    break;
                case 'field':
                    //field permissions
                    $REF = (string) $permission['REF'];
                    if (! in_array($type, $allowed_field_perms)) {
                        $this->logger->error("Can not import permission of type $type for field.");
                        break;
                    }
                    if (! isset($xml_mapping[$REF])) {
                        $this->logger->error("Unknow ref to field $REF.");
                        break;
                    }
                    $this->logger->debug("Adding '$type' permission to '$ugroup_name' on field '$REF'.");
                    $xml_mapping[$REF]->setCachePermission($ugroup_id, $type);
                    break;
                default:
                    break;
            }
        }
    }

    protected function getNotificationsLevel(SimpleXMLElement $xml): int
    {
        $deprecated_stop_notification = isset($xml['stop_notification'])
            ? (int) $xml['stop_notification']
            : \TrackerXmlImport::DEFAULT_NOTIFICATIONS_LEVEL;

        return isset($xml['notifications_level'])
            ? (int) $xml['notifications_level']
            : $deprecated_stop_notification;
    }

    private function getXMLReference(Tracker_FormElement_Field $field, array $xml_fields_mapping): string
    {
        $xml_id = array_search($field, $xml_fields_mapping, true);
        if ($xml_id === false) {
            return '';
        }

        return $xml_id;
    }

    private function getFormElementsFromXml(SimpleXMLElement $xml): array
    {
        $children = $xml->formElements->children();
        if ($children === null) {
            return [];
        }

        $form_element = [];
        foreach ($children as $index => $elem) {
            if ($index === Tracker_FormElement::XML_TAG) {
                $form_element[] = $elem;
            }
            if ($index === Tracker_FormElement::XML_TAG_EXTERNAL_FIELD) {
                $form_element[] = $elem;
            }
        }

        return $form_element;
    }
}
