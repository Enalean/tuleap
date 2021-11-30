<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\XML;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\CannotPlanIntoItselfException;
use Tuleap\ProgramManagement\Domain\Program\Plan\CreatePlan;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanChange;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanIterationChange;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanProgramIncrementChange;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

final class ProgramManagementConfigXMLImporter
{
    private LoggerInterface $logger;
    private ExtractXMLConfig $xml_config_extractor;
    private CreatePlan $plan_creator;
    private ParseXMLConfig $xml_config_parser;

    public function __construct(
        CreatePlan $plan_creator,
        ParseXMLConfig $xml_config_parser,
        ExtractXMLConfig $xml_config_extractor,
        LoggerInterface $logger,
    ) {
        $this->plan_creator         = $plan_creator;
        $this->xml_config_parser    = $xml_config_parser;
        $this->xml_config_extractor = $xml_config_extractor;
        $this->logger               = $logger;
    }

    public function import(
        ProgramForAdministrationIdentifier $program_identifier,
        string $extraction_path,
        array $created_trackers_mapping,
        UserReference $user,
    ): void {
        if (! $this->xml_config_parser->isThereAConfigToImport($extraction_path)) {
            $this->logger->info('[ProgramManagementConfigXMLImporter] No config to be imported');
            return;
        }

        try {
            $this->createConfig(
                $program_identifier,
                $user,
                ProgramManagementXMLConfig::fromXML(
                    $this->xml_config_parser,
                    $this->xml_config_extractor,
                    $program_identifier,
                    $extraction_path,
                    $created_trackers_mapping
                )
            );
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf(
                    "[ProgramManagementConfigXMLImporter] Import has failed: %s",
                    $exception->getMessage()
                )
            );

            return;
        }

        $this->logger->info('[ProgramManagementConfigXMLImporter] Configuration imported successfully');
    }

    /**
     * @throws CannotPlanIntoItselfException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\InvalidProgramUserGroup
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Admin\ProgramCannotBeATeamException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\ProgramIncrementAndIterationCanNotBeTheSameTrackerException
     */
    private function createConfig(
        ProgramForAdministrationIdentifier $project,
        UserReference $user,
        ProgramManagementXMLConfig $xml_config,
    ): void {
        $plan_program_increment_change = new PlanProgramIncrementChange(
            $xml_config->increments_source_tracker_id,
            $xml_config->program_increments_section_name,
            $xml_config->program_increments_milestones_name
        );

        $iteration_representation = null;
        if ($xml_config->iterations_source_tracker_id) {
            $iteration_representation = new PlanIterationChange(
                $xml_config->iterations_source_tracker_id,
                $xml_config->iterations_section_name,
                $xml_config->iterations_milestones_name
            );
        }

        $plan_change = PlanChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $project->id,
            $xml_config->increments_plannable_trackers_ids,
            $xml_config->ugroups_that_can_prioritize_increments,
            $iteration_representation
        );

        $this->plan_creator->create($plan_change);
    }
}
