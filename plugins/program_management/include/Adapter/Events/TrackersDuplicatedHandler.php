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
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;
use Tuleap\Project\MappingRegistry;
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
                    ->map(fn(ProgramForAdministrationIdentifier $new_program) => new ProgramInheritanceMapping(
                        $source_program,
                        $new_program,
                        $this->buildTrackersMapping($event->mapping_registry, $new_program),
                        $this->buildUserGroupsMapping($event->mapping_registry, $new_program)
                    ));
            })
            ->apply(function (ProgramInheritanceMapping $mapping) {
                $this->inheritance_handler->handle($mapping)->mapErr(
                    function (Fault $fault) {
                        Fault::writeToLogger($fault, $this->logger, LogLevel::DEBUG);
                    }
                );
            });
    }

    /**
     * @return array<int, NewConfigurationTrackerIsValidCertificate>
     */
    private function buildTrackersMapping(
        MappingRegistry $mapping_registry,
        ProgramForAdministrationIdentifier $new_program,
    ): array {
        $tracker_mapping = $mapping_registry->getCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY);
        $mapping         = [];
        foreach ($tracker_mapping as $source_tracker_id => $new_tracker_id) {
            $mapping[$source_tracker_id] = new NewConfigurationTrackerIsValidCertificate($new_tracker_id, $new_program);
        }
        return $mapping;
    }

    /**
     * @return array<int, NewUserGroupThatCanPrioritizeIsValidCertificate>
     */
    private function buildUserGroupsMapping(
        MappingRegistry $mapping_registry,
        ProgramForAdministrationIdentifier $new_program,
    ): array {
        $dynamic_ugroups = \Psl\Range\between(\ProjectUGroup::ANONYMOUS, \ProjectUGroup::DYNAMIC_UPPER_BOUNDARY);
        $mapping         = [];
        foreach ($dynamic_ugroups as $dynamic_ugroup_id) {
            // Dynamic user groups such as Project Members are not part of the mapping but are still created implicitly
            $mapping[$dynamic_ugroup_id] = new NewUserGroupThatCanPrioritizeIsValidCertificate($dynamic_ugroup_id, $new_program);
        }
        $user_group_mapping = $mapping_registry->getUgroupMapping();
        foreach ($user_group_mapping as $source_user_group_id => $new_user_group_id) {
            $mapping[$source_user_group_id] = new NewUserGroupThatCanPrioritizeIsValidCertificate($new_user_group_id, $new_program);
        }
        return $mapping;
    }
}
