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
use Psr\Log\LogLevel;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance\PlanInheritanceHandler;
use Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance\ProgramInheritanceMapping;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
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
            ->andThen(function (ProgramIdentifier $source_program) use ($event) {
                if (! $event->mapping_registry->hasCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY)) {
                    return Option::nothing(ProgramInheritanceMapping::class);
                }
                return $this->program_certifier->certifyProgramServiceEnabled($event->new_project)
                    ->map(ProgramForAdministrationIdentifier::fromServiceEnabled(...))
                    ->map(static fn(ProgramForAdministrationIdentifier $new_program) => new ProgramInheritanceMapping(
                        $source_program,
                        $new_program,
                        $event->mapping_registry->getCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY),
                    ));
            })
            ->apply(function (ProgramInheritanceMapping $mapping) {
                $this->inheritance_handler->handle($mapping)->match(
                    function (NewPlanConfiguration $new_plan_configuration) use ($mapping) {
                        $new_program_id   = sprintf(
                            'new program id #%s',
                            $mapping->new_program->id
                        );
                        $pi_tracker_id    = sprintf(
                            'new program increment tracker id #%s',
                            $new_plan_configuration->program_increment_tracker->id
                        );
                        $pi_labels        = sprintf(
                            "new program increment label '%s' and sub-label '%s'",
                            $new_plan_configuration->program_increment_tracker->label ?? '',
                            $new_plan_configuration->program_increment_tracker->sub_label ?? ''
                        );
                        $iteration_id     = sprintf(
                            'new iteration tracker id #%s',
                            (string) $new_plan_configuration->iteration_tracker->unwrapOr(null)?->id
                        );
                        $iteration_labels = sprintf(
                            "new iteration label '%s' and sub-label '%s'",
                            $new_plan_configuration->iteration_tracker->unwrapOr(null)?->label ?? '',
                            $new_plan_configuration->iteration_tracker->unwrapOr(null)?->sub_label ?? ''
                        );

                        $this->logger->debug(
                            sprintf(
                                'Plan configuration inheritance : %s %s %s %s %s',
                                $new_program_id,
                                $pi_tracker_id,
                                $pi_labels,
                                $iteration_id,
                                $iteration_labels
                            )
                        );
                    },
                    function (Fault $fault) {
                        Fault::writeToLogger($fault, $this->logger, LogLevel::DEBUG);
                    }
                );
            });
    }
}
