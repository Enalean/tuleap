<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Exporter;

use Tracker;
use Tracker_CannedResponseFactory;
use Tracker_ReportFactory;
use Tracker_RulesManager;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\RetrieveFormElementsForTracker;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Webhook\WebhookXMLExporter;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowXMLExporter;
use Tuleap\Tracker\XML\XMLTracker;
use UserXMLExporter;
use WorkflowFactory;

final class TrackerStructureXMLExporter
{
    private const TRACKER_NOT_USE_PRIVATE_COMMENTS_EXPORT_XML          = "0";
    private const TRACKER_SHOULD_SEND_EVENT_IN_NOTIFICATION_EXPORT_XML = '1';

    public function __construct(
        private readonly PromotedTrackerDao $promoted_tracker_dao,
        private readonly TrackerPrivateCommentUGroupEnabledDao $private_comment_u_group_enabled_dao,
        private readonly CheckEventShouldBeSentInNotification $calendar_event_config_dao,
        private readonly Tracker_CannedResponseFactory $canned_response_factory,
        private readonly RetrieveFormElementsForTracker $form_element_factory,
        private readonly Tracker_RulesManager $tracker_rules_manager,
        private readonly Tracker_ReportFactory $tracker_report_factory,
        private readonly WorkflowFactory $workflow_factory,
        private readonly SimpleWorkflowXMLExporter $simple_workflow_xml_exporter,
        private readonly WebhookXMLExporter $webhook_xml_exporter,
        private readonly MoveActionAllowedChecker $move_action_allowed_checker,
    ) {
    }

    /**
     * Exports the tracker to an XML file.
     */
    public function exportTrackerStructureToXML(
        Tracker $tracker,
        \SimpleXMLElement $xmlElem,
        UserXMLExporter $user_xml_exporter,
        array &$xmlMapping,
        bool $project_export_context,
    ): \SimpleXMLElement {
        if ($project_export_context) {
            $xml_tracker = XMLTracker::fromTracker($tracker);
        } else {
            $xml_tracker = XMLTracker::fromTrackerInProjectContext($tracker);
        }
        $xmlElem = $xml_tracker->exportTracker($xmlElem);

        // only add attributes which are different from the default value
        if ($tracker->isEmailgatewayEnabled()) {
            $xmlElem->addAttribute('enable_emailgateway', $tracker->isEmailgatewayEnabled());
        }
        if ($tracker->isCopyAllowed()) {
            $xmlElem->addAttribute('allow_copy', '1');
        }
        if ($tracker->instantiate_for_new_projects) {
            $xmlElem->addAttribute('instantiate_for_new_projects', (string) $tracker->instantiate_for_new_projects);
        }
        if ($tracker->log_priority_changes) {
            $xmlElem->addAttribute('log_priority_changes', (string) $tracker->log_priority_changes);
        }
        if ($tracker->getNotificationsLevel()) {
            $xmlElem->addAttribute('notifications_level', (string) $tracker->getNotificationsLevel());
        }

        if ($this->promoted_tracker_dao->isContaining($tracker->getId())) {
            $xmlElem->addAttribute('is_displayed_in_new_dropdown', (string) true);
        }

        if (! $this->private_comment_u_group_enabled_dao->isTrackerEnabledPrivateComment($tracker->getId())) {
            $xmlElem->addAttribute('use_private_comments', self::TRACKER_NOT_USE_PRIVATE_COMMENTS_EXPORT_XML);
        }

        if ($this->calendar_event_config_dao->shouldSendEventInNotification($tracker->getId())) {
            $xmlElem->addAttribute('should_send_event_in_notification', self::TRACKER_SHOULD_SEND_EVENT_IN_NOTIFICATION_EXPORT_XML);
        }

        ($this->move_action_allowed_checker)->checkMoveActionIsAllowedInTracker($tracker)
            ->match(
                fn () => $xmlElem->addAttribute('enable_move_artifacts', '1'),
                fn () => $xmlElem->addAttribute('enable_move_artifacts', '0'),
            );

        if ($responses = $this->canned_response_factory->getCannedResponses($tracker)) {
            foreach ($responses as $response) {
                $grandchild = $xmlElem->cannedResponses->addChild('cannedResponse');
                $response->exportToXML($grandchild);
            }
        }

        foreach ($this->form_element_factory->getUsedFormElementForTracker($tracker) as $formElement) {
            $formElement->exportToXML($xmlElem->formElements, $xmlMapping, $project_export_context, $user_xml_exporter);
        }

        // semantic
        $tracker_semantic_manager = $tracker->getTrackerSemanticManager();
        $child                    = $xmlElem->addChild('semantics');
        if ($child === null) {
            throw new \LogicException('XML content is not created.');
        }
        $tracker_semantic_manager->exportToXML($child, $xmlMapping);

        // rules
        $child = $xmlElem->addChild('rules');
        if ($child === null) {
            throw new \LogicException('XML content is not created.');
        }
        $this->tracker_rules_manager->exportToXML($child, $xmlMapping);

        // only the reports with project scope are exported
        $reports = $this->tracker_report_factory->getReportsByTrackerId($tracker->getId(), null);
        if ($reports) {
            $child = $xmlElem->addChild('reports');
            if ($child === null) {
                throw new \LogicException('XML content is not created.');
            }
            foreach ($reports as $report) {
                $report->exportToXML($child, $xmlMapping);
            }
        }

        // workflow
        $workflow = $this->workflow_factory->getWorkflowByTrackerId($tracker->getId());
        if (! empty($workflow)) {
            if (! $workflow->isAdvanced()) {
                $child = $xmlElem->addChild('simple_workflow');
                if ($child === null) {
                    throw new \LogicException('XML content is not created.');
                }
                $this->simple_workflow_xml_exporter->exportToXML($workflow, $child, $xmlMapping);
            } else {
                $child = $xmlElem->addChild('workflow');
                if ($child === null) {
                    throw new \LogicException('XML content is not created.');
                }
                $workflow->exportToXML($child, $xmlMapping);
            }
        }

        $this->webhook_xml_exporter->exportTrackerWebhooksInXML($xmlElem, $tracker);

        // permissions
        $node_perms = $xmlElem->addChild('permissions');
        if ($node_perms === null) {
            throw new \LogicException('XML content is not created.');
        }
        $project_ugroups = $tracker->getUGroupRetrieverWithLegacy()->getProjectUgroupIds($tracker->getProject());
        // tracker permissions
        if ($permissions = $tracker->getPermissionsByUgroupId()) {
            foreach ($permissions as $ugroup_id => $permission_types) {
                if (($ugroup = array_search($ugroup_id, $project_ugroups)) !== false) {
                    foreach ($permission_types as $permission_type) {
                        $node_perm = $node_perms->addChild('permission');
                        if ($node_perm === null) {
                            throw new \LogicException('XML content is not created.');
                        }
                        $node_perm->addAttribute('scope', 'tracker');
                        $node_perm->addAttribute('ugroup', $ugroup);
                        $node_perm->addAttribute('type', $permission_type);
                        unset($node_perm);
                    }
                }
            }
        }
        // fields permission
        if ($formelements = $this->form_element_factory->getUsedFormElementForTracker($tracker)) {
            foreach ($formelements as $formelement) {
                $formelement->exportPermissionsToXML($node_perms, $project_ugroups, $xmlMapping);
            }
        }

        return $xmlElem;
    }
}
