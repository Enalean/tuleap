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

namespace Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramUserGroupCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewIterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlanConfiguration;

final readonly class PlanInheritanceHandler
{
    public function __construct(
        private RetrievePlanConfiguration $retrieve_plan,
    ) {
    }

    /** @return Ok<NewPlanConfiguration> | Err<Fault> */
    public function handle(ProgramInheritanceMapping $mapping): Ok|Err
    {
        $configuration                       = $this->retrieve_plan->retrievePlan($mapping->source_program);
        $source_program_increment_tracker_id = $configuration->program_increment_tracker->getId();

        if (! isset($mapping->tracker_mapping[$source_program_increment_tracker_id])) {
            return Result::err(
                ProgramIncrementTrackerNotFoundInMappingFault::build(
                    $mapping->source_program->getId(),
                    $mapping->new_program->id,
                    $source_program_increment_tracker_id
                )
            );
        }
        $new_program_increment_tracker_id = $mapping->tracker_mapping[$source_program_increment_tracker_id];

        $new_program_increment = NewProgramIncrementTracker::fromValidTrackerAndLabels(
            new NewConfigurationTrackerIsValidCertificate($new_program_increment_tracker_id, $mapping->new_program),
            $configuration->program_increment_labels->label,
            $configuration->program_increment_labels->sub_label
        );

        $new_iteration = $this->mapIterationConfiguration(
            $mapping,
            $configuration->iteration_tracker,
            $configuration->iteration_labels
        );

        $incomplete_new_plan_configuration = new NewPlanConfiguration(
            $new_program_increment,
            $mapping->new_program,
            [],
            ProgramUserGroupCollection::buildFakeCollection(),
            $new_iteration
        );

        return Result::ok($incomplete_new_plan_configuration);
    }

    /**
     * @param Option<IterationTrackerIdentifier> $iteration_tracker
     * @return Option<NewIterationTrackerConfiguration>
     */
    private function mapIterationConfiguration(
        ProgramInheritanceMapping $mapping,
        Option $iteration_tracker,
        IterationLabels $iteration_labels,
    ): Option {
        return $iteration_tracker->andThen(static function (IterationTrackerIdentifier $iteration) use (
            $iteration_labels,
            $mapping
        ) {
            if (! isset($mapping->tracker_mapping[$iteration->getId()])) {
                return Option::nothing(NewIterationTrackerConfiguration::class);
            }
            $new_iteration_tracker_id = $mapping->tracker_mapping[$iteration->getId()];

            return Option::fromValue(
                NewIterationTrackerConfiguration::fromValidTrackerAndLabels(
                    new NewConfigurationTrackerIsValidCertificate($new_iteration_tracker_id, $mapping->new_program),
                    $iteration_labels->label,
                    $iteration_labels->sub_label
                )
            );
        });
    }
}
