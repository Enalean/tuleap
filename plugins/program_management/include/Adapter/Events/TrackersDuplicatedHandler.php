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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanInheritanceHandler;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;

final readonly class TrackersDuplicatedHandler
{
    public function __construct(
        private ProgramServiceIsEnabledCertifier $program_certifier,
        private PlanInheritanceHandler $inheritance_handler,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(TrackerEventTrackersDuplicated $event): void
    {
        $this->program_certifier->certifyProgramServiceEnabled($event->source_project)
            ->map(ProgramIdentifier::fromServiceEnabled(...))
            ->apply(function (ProgramIdentifier $program_identifier) {
                $plan_configuration      = $this->inheritance_handler->handle($program_identifier);
                $program_id              = sprintf('program id #%s', $plan_configuration->program_identifier->getId());
                $pi_tracker_id           = sprintf('program increment tracker id #%s', $plan_configuration->program_increment_tracker->getId());
                $iteration_tracker_id    = sprintf(
                    'iteration tracker id #%s',
                    (string) $plan_configuration->iteration_tracker->unwrapOr(null)?->getId()
                );
                $pi_labels               = sprintf(
                    "program increment label '%s' and sub-label '%s'",
                    $plan_configuration->program_increment_labels->label ?? '',
                    $plan_configuration->program_increment_labels->sub_label ?? ''
                );
                $iteration_labels        = sprintf(
                    "iteration label '%s' and sub-label '%s'",
                    $plan_configuration->iteration_labels->label ?? '',
                    $plan_configuration->iteration_labels->sub_label ?? '',
                );
                $trackers_can_be_planned = sprintf('tracker ids that can be planned: %s', \Psl\Json\encode($plan_configuration->tracker_ids_that_can_be_planned));
                $user_groups_can_prio    = sprintf('user group ids that can plan: %s', \Psl\Json\encode($plan_configuration->user_group_ids_that_can_prioritize));
                $this->logger->debug(
                    sprintf(
                        'Plan configuration retrieved from template : %s %s %s %s %s %s %s',
                        $program_id,
                        $pi_tracker_id,
                        $iteration_tracker_id,
                        $pi_labels,
                        $iteration_labels,
                        $trackers_can_be_planned,
                        $user_groups_can_prio
                    )
                );
            });
    }
}
