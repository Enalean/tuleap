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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Psr\Log\LoggerInterface;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;

class WorkflowChecker
{
    /**
     * @var \Workflow_Dao
     */
    private $workflow_dao;
    /**
     * @var \Tracker_Rule_Date_Dao
     */
    private $tracker_rule_date_dao;
    /**
     * @var \Tracker_Rule_List_Dao
     */
    private $tracker_rule_list_dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Workflow_Dao $workflow_dao,
        \Tracker_Rule_Date_Dao $tracker_rule_date_dao,
        \Tracker_Rule_List_Dao $tracker_rule_list_dao,
        LoggerInterface $logger
    ) {
        $this->workflow_dao = $workflow_dao;
        $this->tracker_rule_date_dao = $tracker_rule_date_dao;
        $this->tracker_rule_list_dao = $tracker_rule_list_dao;
        $this->logger = $logger;
    }

    public function areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
        ProgramIncrementsTrackerCollection $program_increment_trackers,
        SynchronizedFieldDataFromProgramAndTeamTrackersCollection $field_collection
    ): bool {
        return $this->areTransitionRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
            $program_increment_trackers,
            $field_collection
        ) && $this->areDateRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
            $program_increment_trackers,
            $field_collection
        ) && $this->areListRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
            $program_increment_trackers,
            $field_collection
        );
    }

    private function areTransitionRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
        ProgramIncrementsTrackerCollection $program_increment_trackers,
        SynchronizedFieldDataFromProgramAndTeamTrackersCollection $field_collection
    ): bool {
        $workflow_transition_rules = $this->workflow_dao->searchWorkflowsByFieldIDsAndTrackerIDs(
            $program_increment_trackers->getTrackerIds(),
            $field_collection->getSynchronizedFieldIDs()
        );

        if (count($workflow_transition_rules) > 0) {
            $tracker_ids_with_transition_rules_on_synchronized_fields = [];
            foreach ($workflow_transition_rules as $workflow_transition_rule) {
                $tracker_ids_with_transition_rules_on_synchronized_fields[] = $workflow_transition_rule['tracker_id'];
            }
            $this->logger->debug(
                sprintf(
                    "Following tracker # are using a synchronized field in a workflow transition rule: %s",
                    implode(', ', $tracker_ids_with_transition_rules_on_synchronized_fields)
                )
            );
            return false;
        }

        return true;
    }

    private function areDateRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
        ProgramIncrementsTrackerCollection $program_increment_trackers,
        SynchronizedFieldDataFromProgramAndTeamTrackersCollection $field_collection
    ): bool {
        $tracker_ids_with_date_rules = $this->tracker_rule_date_dao->searchTrackersWithRulesByFieldIDsAndTrackerIDs(
            $program_increment_trackers->getTrackerIds(),
            $field_collection->getSynchronizedFieldIDs()
        );

        if (count($tracker_ids_with_date_rules) > 0) {
            $this->logger->debug(
                sprintf(
                    "Following tracker # are using a synchronized field in a workflow date rule (global rules): %s",
                    implode(', ', $tracker_ids_with_date_rules)
                )
            );
            return false;
        }

        return true;
    }

    private function areListRulesNotUsedWithSynchronizedFieldsInTeamTrackers(
        ProgramIncrementsTrackerCollection $program_increment_trackers,
        SynchronizedFieldDataFromProgramAndTeamTrackersCollection $field_collection
    ): bool {
        $tracker_ids_with_list_rules = $this->tracker_rule_list_dao->searchTrackersWithRulesByFieldIDsAndTrackerIDs(
            $program_increment_trackers->getTrackerIds(),
            $field_collection->getSynchronizedFieldIDs()
        );

        if (count($tracker_ids_with_list_rules) > 0) {
            $this->logger->debug(
                sprintf(
                    "Following tracker # are using a synchronized field in a workflow list rule (field dependencies): %s",
                    implode(', ', $tracker_ids_with_list_rules)
                )
            );
            return false;
        }

        return true;
    }
}
