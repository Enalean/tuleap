<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Psr\Log\LoggerInterface;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollection;

class WorkflowChecker
{
    /**
     * @var \Workflow_Dao
     */
    private $workflow_dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\Workflow_Dao $workflow_dao, LoggerInterface $logger)
    {
        $this->workflow_dao = $workflow_dao;
        $this->logger       = $logger;
    }

    public function areWorkflowsNotUsedWithSynchronizedFieldsInContributorTrackers(
        MilestoneTrackerCollection $tracker_collection,
        SynchronizedFieldCollection $field_collection
    ): bool {
        $workflow_transition_rules = $this->workflow_dao->searchWorkflowsByFieldIDsAndTrackerIDs(
            $tracker_collection->getContributorMilestoneTrackerIds(),
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
}
