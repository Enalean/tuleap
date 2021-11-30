<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\XML;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramManagementXMLConfig
{
    public int $increments_source_tracker_id;
    public ?int $iterations_source_tracker_id;

    /**
     * @var int[]
     */
    public array $increments_plannable_trackers_ids;
    /**
     * @var string[]
     */
    public array $ugroups_that_can_prioritize_increments;

    public ?string $program_increments_section_name;
    public ?string $program_increments_milestones_name;
    public ?string $iterations_section_name;
    public ?string $iterations_milestones_name;

    /**
     * @param int[] $increments_plannable_trackers_ids
     * @param string[] $ugroups_that_can_prioritize_increments
     */
    private function __construct(
        int $increments_source_tracker_id,
        array $increments_plannable_trackers_ids,
        array $ugroups_that_can_prioritize_increments,
        ?string $program_increments_section_name,
        ?string $program_increments_milestones_name,
        ?int $iterations_source_tracker_id,
        ?string $iterations_section_name,
        ?string $iterations_milestones_name,
    ) {
        $this->increments_source_tracker_id           = $increments_source_tracker_id;
        $this->increments_plannable_trackers_ids      = $increments_plannable_trackers_ids;
        $this->ugroups_that_can_prioritize_increments = $ugroups_that_can_prioritize_increments;
        $this->program_increments_section_name        = $program_increments_section_name;
        $this->program_increments_milestones_name     = $program_increments_milestones_name;
        $this->iterations_source_tracker_id           = $iterations_source_tracker_id;
        $this->iterations_section_name                = $iterations_section_name;
        $this->iterations_milestones_name             = $iterations_milestones_name;
    }

    /**
     * @throws \XML_ParseException
     * @throws Exceptions\CannotFindUserGroupInProjectException
     * @throws Exceptions\CannotFindPlannableTrackerInMappingException
     * @throws Exceptions\CannotFindSourceTrackerUsingXmlReference
     * @throws Exceptions\CannotFindXMLNodeAttributeException
     * @throws Exceptions\CannotLoadXMLConfigFileException
     */
    public static function fromXML(
        ParseXMLConfig $config_parser,
        ExtractXMLConfig $config_extracter,
        ProgramForAdministrationIdentifier $program_identifier,
        string $extraction_path,
        array $created_trackers_mapping,
    ): self {
        $xml_config = $config_parser->parseConfig($extraction_path);
        return new self(
            $config_extracter->getIncrementsSourceTrackerId($xml_config, $created_trackers_mapping),
            $config_extracter->getIncrementsPlannableTrackersIds($xml_config, $created_trackers_mapping),
            $config_extracter->getUgroupsIdsThatCanPrioritizeIncrements($xml_config, $program_identifier),
            $config_extracter->getCustomProgramIncrementsSectionName($xml_config),
            $config_extracter->getCustomProgramIncrementsMilestonesName($xml_config),
            $config_extracter->getIterationsSourceTrackerId($xml_config, $created_trackers_mapping),
            $config_extracter->getCustomIterationsSectionName($xml_config),
            $config_extracter->getCustomIterationsMilestonesName($xml_config)
        );
    }
}
