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

namespace Tuleap\ProgramManagement\Domain\XML;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramManagementXMLConfig
{
    public int $source_tracker_id;
    /**
     * @var int[]
     */
    public array $plannable_trackers_ids;
    /**
     * @var string[]
     */
    public array $ugroups_that_can_prioritize;

    public ?string $program_increments_section_name;
    public ?string $milestones_name;

    /**
     * @param int[] $plannable_trackers_ids
     * @param string[] $ugroups_that_can_prioritize
     */
    private function __construct(
        int $source_tracker_id,
        array $plannable_trackers_ids,
        array $ugroups_that_can_prioritize,
        ?string $program_increments_section_name,
        ?string $milestones_name
    ) {
        $this->source_tracker_id               = $source_tracker_id;
        $this->plannable_trackers_ids          = $plannable_trackers_ids;
        $this->ugroups_that_can_prioritize     = $ugroups_that_can_prioritize;
        $this->program_increments_section_name = $program_increments_section_name;
        $this->milestones_name                 = $milestones_name;
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
        array $created_trackers_mapping
    ): self {
        $xml_config = $config_parser->parseConfig($extraction_path);
        return new self(
            $config_extracter->getSourceTrackerId($xml_config, $created_trackers_mapping),
            $config_extracter->getPlannableTrackersIds($xml_config, $created_trackers_mapping),
            $config_extracter->getUgroupsIdsThatCanPrioritize($xml_config, $program_identifier),
            $config_extracter->getCustomProgramIncrementsSectionName($xml_config),
            $config_extracter->getCustomMilestonesName($xml_config)
        );
    }
}
