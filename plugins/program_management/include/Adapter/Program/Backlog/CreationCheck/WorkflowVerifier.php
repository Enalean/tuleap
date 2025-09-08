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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifySynchronizedFieldsAreNotUsedInWorkflow;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;

final class WorkflowVerifier implements VerifySynchronizedFieldsAreNotUsedInWorkflow
{
    private \Workflow_Dao $workflow_dao;
    private \Tracker_Rule_Date_Dao $tracker_rule_date_dao;
    private \Tracker_Rule_List_Dao $tracker_rule_list_dao;
    private \TrackerFactory $tracker_factory;

    public function __construct(
        \Workflow_Dao $workflow_dao,
        \Tracker_Rule_Date_Dao $tracker_rule_date_dao,
        \Tracker_Rule_List_Dao $tracker_rule_list_dao,
        \TrackerFactory $tracker_factory,
    ) {
        $this->workflow_dao          = $workflow_dao;
        $this->tracker_rule_date_dao = $tracker_rule_date_dao;
        $this->tracker_rule_list_dao = $tracker_rule_list_dao;
        $this->tracker_factory       = $tracker_factory;
    }

    #[\Override]
    public function areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
        TrackerCollection $trackers,
        SynchronizedFieldFromProgramAndTeamTrackersCollection $field_collection,
        ConfigurationErrorsCollector $errors_collector,
    ): bool {
        $workflow_used = $this->areTransitionRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
            $trackers,
            $field_collection,
            $errors_collector
        );
        if (! $errors_collector->shouldCollectAllIssues() && ! $workflow_used) {
            return false;
        }
        $date_transition_used = $this->areDateRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
            $trackers,
            $field_collection,
            $errors_collector
        );
        if (! $errors_collector->shouldCollectAllIssues() && ! $date_transition_used) {
            return false;
        }

        $list_transition_used = $this->areListRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
            $trackers,
            $field_collection,
            $errors_collector
        );

        return $workflow_used && $date_transition_used && $list_transition_used;
    }

    private function areTransitionRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
        TrackerCollection $trackers,
        SynchronizedFieldFromProgramAndTeamTrackersCollection $field_collection,
        ConfigurationErrorsCollector $errors_collector,
    ): bool {
        $workflow_transition_rules = $this->workflow_dao->searchWorkflowsByFieldIDsAndTrackerIDs(
            $trackers->getTrackerIds(),
            $field_collection->getSynchronizedFieldIDs()
        );

        $is_valid = true;
        if (count($workflow_transition_rules) > 0) {
            foreach ($workflow_transition_rules as $rule) {
                $tracker = $this->tracker_factory->getTrackerById($rule['tracker_id']);
                if ($tracker) {
                    $errors_collector->addWorkflowTransitionRulesError(
                        TrackerReferenceProxy::fromTracker($tracker),
                        ProjectProxy::buildFromProject($tracker->getProject())
                    );
                }
            }
            $is_valid = false;
            if (! $errors_collector->shouldCollectAllIssues()) {
                return $is_valid;
            }
        }

        return $is_valid;
    }

    private function areDateRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
        TrackerCollection $trackers,
        SynchronizedFieldFromProgramAndTeamTrackersCollection $field_collection,
        ConfigurationErrorsCollector $errors_collector,
    ): bool {
        $tracker_ids_with_date_rules = $this->tracker_rule_date_dao->searchTrackersWithRulesByFieldIDsAndTrackerIDs(
            $trackers->getTrackerIds(),
            $field_collection->getSynchronizedFieldIDs()
        );

        $is_valid = true;
        if (count($tracker_ids_with_date_rules) > 0) {
            foreach ($tracker_ids_with_date_rules as $tracker_id) {
                $tracker = $this->tracker_factory->getTrackerById($tracker_id);
                if ($tracker) {
                    $errors_collector->addWorkflowTransitionDateRulesError(
                        TrackerReferenceProxy::fromTracker($tracker),
                        ProjectProxy::buildFromProject($tracker->getProject())
                    );
                }
            }
            $is_valid = false;
            if (! $errors_collector->shouldCollectAllIssues()) {
                return $is_valid;
            }
        }

        return $is_valid;
    }

    private function areListRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
        TrackerCollection $trackers,
        SynchronizedFieldFromProgramAndTeamTrackersCollection $field_collection,
        ConfigurationErrorsCollector $errors_collector,
    ): bool {
        $tracker_ids_with_list_rules = $this->tracker_rule_list_dao->searchTrackersWithRulesByFieldIDsAndTrackerIDs(
            $trackers->getTrackerIds(),
            $field_collection->getSynchronizedFieldIDs()
        );

        $is_valid = true;
        if (count($tracker_ids_with_list_rules) > 0) {
            foreach ($tracker_ids_with_list_rules as $tracker_id) {
                $tracker = $this->tracker_factory->getTrackerById($tracker_id);
                if ($tracker) {
                    $errors_collector->addWorkflowDependencyError(
                        TrackerReferenceProxy::fromTracker($tracker),
                        ProjectProxy::buildFromProject($tracker->getProject())
                    );
                }
            }
            $is_valid = false;
            if (! $errors_collector->shouldCollectAllIssues()) {
                return $is_valid;
            }
        }

        return $is_valid;
    }
}
