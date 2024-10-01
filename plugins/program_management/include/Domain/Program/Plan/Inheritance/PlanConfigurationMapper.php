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
use Tuleap\ProgramManagement\Domain\Program\Admin\CollectionOfNewUserGroupsThatCanPrioritize;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewIterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewTrackerThatCanBePlanned;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewTrackerThatCanBePlannedCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewUserGroupThatCanPrioritize;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;

final readonly class PlanConfigurationMapper
{
    /**
     * @return Ok<NewPlanConfiguration> | Err<Fault>
     */
    public function mapFromTemplateProgramToNewProgram(
        ProgramInheritanceMapping $mapping,
        PlanConfiguration $template_configuration,
    ): Ok|Err {
        $source_program_increment_tracker_id = $template_configuration->program_increment_tracker->getId();

        return $mapping->getMappedTrackerId($source_program_increment_tracker_id)
            ->okOr(
                Result::err(
                    ProgramIncrementTrackerNotFoundInMappingFault::build(
                        $mapping->source_program->getId(),
                        $mapping->new_program->id,
                        $source_program_increment_tracker_id
                    )
                )
            )->map(function (NewConfigurationTrackerIsValidCertificate $new_program_increment_certificate) use ($mapping, $template_configuration) {
                $new_program_increment = NewProgramIncrementTracker::fromValidTrackerAndLabels(
                    $new_program_increment_certificate,
                    $template_configuration->program_increment_labels->label,
                    $template_configuration->program_increment_labels->sub_label
                );

                $new_trackers_that_can_be_planned = $this->mapTrackersThatCanBePlanned(
                    $mapping,
                    $template_configuration->tracker_ids_that_can_be_planned
                );

                $new_user_groups_that_can_prioritize = $this->mapUserGroupsThatCanPrioritize(
                    $mapping,
                    $template_configuration->user_group_ids_that_can_prioritize
                );

                $new_iteration = $this->mapIterationConfiguration(
                    $mapping,
                    $template_configuration->iteration_tracker,
                    $template_configuration->iteration_labels
                );

                return new NewPlanConfiguration(
                    $new_program_increment,
                    $mapping->new_program,
                    $new_trackers_that_can_be_planned,
                    $new_user_groups_that_can_prioritize,
                    $new_iteration
                );
            });
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
        return $iteration_tracker->andThen(
            static fn(IterationTrackerIdentifier $iteration) => $mapping->getMappedTrackerId($iteration->getId())
        )->map(
            static fn(NewConfigurationTrackerIsValidCertificate $new_iteration_certificate) => NewIterationTrackerConfiguration::fromValidTrackerAndLabels(
                $new_iteration_certificate,
                $iteration_labels->label,
                $iteration_labels->sub_label
            )
        );
    }

    /**
     * @param list<int> $trackers_from_template
     */
    private function mapTrackersThatCanBePlanned(
        ProgramInheritanceMapping $mapping,
        array $trackers_from_template,
    ): NewTrackerThatCanBePlannedCollection {
        $new_trackers = [];
        foreach ($trackers_from_template as $tracker_id_from_template) {
            $mapping->getMappedTrackerId($tracker_id_from_template)
                ->map(NewTrackerThatCanBePlanned::fromValidTracker(...))
                ->apply(static function (NewTrackerThatCanBePlanned $new_tracker) use (&$new_trackers) {
                    $new_trackers[] = $new_tracker;
                });
        }
        return NewTrackerThatCanBePlannedCollection::fromTrackers($new_trackers);
    }

    /**
     * @param list<int> $user_groups_from_template
     */
    private function mapUserGroupsThatCanPrioritize(
        ProgramInheritanceMapping $mapping,
        array $user_groups_from_template,
    ): CollectionOfNewUserGroupsThatCanPrioritize {
        $new_user_groups = [];
        foreach ($user_groups_from_template as $user_group_id_from_template) {
            $mapping->getMappedUserGroupId($user_group_id_from_template)
                ->map(NewUserGroupThatCanPrioritize::fromValidUserGroup(...))
                ->apply(static function (NewUserGroupThatCanPrioritize $new_user_group) use (&$new_user_groups) {
                    $new_user_groups[] = $new_user_group;
                });
        }
        return CollectionOfNewUserGroupsThatCanPrioritize::fromUserGroups($new_user_groups);
    }
}
