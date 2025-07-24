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

namespace Tuleap\ProgramManagement\Tests\Stub;

use SimpleXMLElement;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Adapter\XML\ExtractXMLConfig;

final class ExtractXMLConfigStub implements ExtractXMLConfig
{
    private bool $will_extraction_fail = false;

    private ?int $source_tracker_id;
    private ?int $iterations_source_tracker_id;

    private ?array $plannable_trackers_ids;
    private ?array $ugroups_that_can_prioritize;

    private ?string $program_increments_section_name;
    private ?string $milestones_name;
    private ?string $iterations_section_name;
    private ?string $iterations_milestones_name;

    private function __construct(
        ?int $source_tracker_id,
        ?array $plannable_trackers_ids,
        ?array $ugroups_that_can_prioritize,
        ?string $program_increments_section_name,
        ?string $milestones_name,
        ?int $iterations_source_tracker_id,
        ?string $iterations_section_name,
        ?string $iterations_milestones_name,
    ) {
        $this->source_tracker_id               = $source_tracker_id;
        $this->plannable_trackers_ids          = $plannable_trackers_ids;
        $this->ugroups_that_can_prioritize     = $ugroups_that_can_prioritize;
        $this->program_increments_section_name = $program_increments_section_name;
        $this->milestones_name                 = $milestones_name;
        $this->iterations_source_tracker_id    = $iterations_source_tracker_id;
        $this->iterations_section_name         = $iterations_section_name;
        $this->iterations_milestones_name      = $iterations_milestones_name;
    }

    public static function buildWithNoConfigToImport(): self
    {
        return new self(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );
    }

    public static function buildWithConfigToImport(
        int $source_tracker_id,
        array $plannable_trackers_ids,
        array $ugroups_that_can_prioritize,
        ?string $program_increments_section_name,
        ?string $milestones_name,
        ?int $iterations_source_tracker_id,
        ?string $iterations_section_name,
        ?string $iterations_milestones_name,
    ): self {
        return new self(
            $source_tracker_id,
            $plannable_trackers_ids,
            $ugroups_that_can_prioritize,
            $program_increments_section_name,
            $milestones_name,
            $iterations_source_tracker_id,
            $iterations_section_name,
            $iterations_milestones_name
        );
    }

    #[\Override]
    public function getIncrementsSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): int
    {
        if ($this->source_tracker_id === null || $this->will_extraction_fail) {
            throw new CannotFindSourceTrackerUsingXmlReference('T1234');
        }

        return $this->source_tracker_id;
    }

    #[\Override]
    public function getIncrementsPlannableTrackersIds(SimpleXMLElement $xml_config, array $created_trackers_mapping): array
    {
        if ($this->plannable_trackers_ids === null || $this->will_extraction_fail) {
            throw new CannotFindPlannableTrackerInMappingException('T1234');
        }

        return $this->plannable_trackers_ids;
    }

    #[\Override]
    public function getUgroupsIdsThatCanPrioritizeIncrements(SimpleXMLElement $xml_config, ProgramForAdministrationIdentifier $program_identifier): array
    {
        if ($this->ugroups_that_can_prioritize === null || $this->will_extraction_fail) {
            throw new CannotFindUserGroupInProjectException('Metallica');
        }

        return $this->ugroups_that_can_prioritize;
    }

    #[\Override]
    public function getCustomProgramIncrementsSectionName(SimpleXMLElement $xml_config): ?string
    {
        return $this->program_increments_section_name;
    }

    #[\Override]
    public function getCustomProgramIncrementsMilestonesName(SimpleXMLElement $xml_config): ?string
    {
        return $this->milestones_name;
    }

    public function withFailingExtraction(): void
    {
        $this->will_extraction_fail = true;
    }

    #[\Override]
    public function getIterationsSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): ?int
    {
        if ($this->will_extraction_fail) {
            throw new CannotFindSourceTrackerUsingXmlReference('T1234');
        }

        return $this->iterations_source_tracker_id;
    }

    #[\Override]
    public function getCustomIterationsSectionName(SimpleXMLElement $xml_config): ?string
    {
        return $this->iterations_section_name;
    }

    #[\Override]
    public function getCustomIterationsMilestonesName(SimpleXMLElement $xml_config): ?string
    {
        return $this->iterations_milestones_name;
    }
}
