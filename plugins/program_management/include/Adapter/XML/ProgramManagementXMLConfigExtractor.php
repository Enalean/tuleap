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

use SimpleXMLElement;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindXMLNodeAttributeException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUGroups;
use Tuleap\ProgramManagement\Domain\Workspace\UserGroup;

final class ProgramManagementXMLConfigExtractor implements ExtractXMLConfig
{
    private RetrieveUGroups $ugroup_retriever;

    public function __construct(RetrieveUGroups $ugroup_retriever)
    {
        $this->ugroup_retriever = $ugroup_retriever;
    }

    #[\Override]
    public function getIncrementsSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): int
    {
        $source_tracker_ref = $this->getTargetAttributeValueInXMLNode($xml_config->increments->source_tracker, 'REF');

        if (! isset($created_trackers_mapping[$source_tracker_ref])) {
            throw new CannotFindSourceTrackerUsingXmlReference($source_tracker_ref);
        }

        return (int) $created_trackers_mapping[$source_tracker_ref];
    }

    #[\Override]
    public function getIterationsSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): ?int
    {
        if (! $xml_config->iterations) {
            return null;
        }

        $source_tracker_ref = $this->getTargetAttributeValueInXMLNode($xml_config->iterations->source_tracker, 'REF');

        if (! isset($created_trackers_mapping[$source_tracker_ref])) {
            throw new CannotFindSourceTrackerUsingXmlReference($source_tracker_ref);
        }

        return (int) $created_trackers_mapping[$source_tracker_ref];
    }

    #[\Override]
    public function getIncrementsPlannableTrackersIds(SimpleXMLElement $xml_config, array $created_trackers_mapping): array
    {
        $trackers_ids = [];
        foreach ($xml_config->increments->plannable_trackers->children() as $plannable_tracker) {
            $tracker_ref = $this->getTargetAttributeValueInXMLNode($plannable_tracker, 'REF');

            if (! isset($created_trackers_mapping[$tracker_ref])) {
                throw new CannotFindPlannableTrackerInMappingException($tracker_ref);
            }

            $trackers_ids[] = (int) $created_trackers_mapping[$tracker_ref];
        }
        return $trackers_ids;
    }

    #[\Override]
    public function getUgroupsIdsThatCanPrioritizeIncrements(SimpleXMLElement $xml_config, ProgramForAdministrationIdentifier $program_identifier): array
    {
        $ugroups_that_can_prioritize = [];

        foreach ($xml_config->increments->can_prioritize->children() as $ugroup) {
            $ugroup_name    = $this->getTargetAttributeValueInXMLNode($ugroup, 'ugroup_name');
            $project_ugroup = UserGroup::fromName($this->ugroup_retriever, $program_identifier, $ugroup_name);

            if (! $project_ugroup) {
                throw new CannotFindUserGroupInProjectException($ugroup_name);
            }

            $ugroups_that_can_prioritize[] = implode('_', [$program_identifier->id, $project_ugroup->id]);
        }

        return $ugroups_that_can_prioritize;
    }

    #[\Override]
    public function getCustomProgramIncrementsSectionName(SimpleXMLElement $xml_config): ?string
    {
        if ($xml_config->increments->section_name) {
            return (string) $xml_config->increments->section_name;
        }

        return null;
    }

    #[\Override]
    public function getCustomProgramIncrementsMilestonesName(SimpleXMLElement $xml_config): ?string
    {
        if ($xml_config->increments->milestones_name) {
            return (string) $xml_config->increments->milestones_name;
        }

        return null;
    }

    #[\Override]
    public function getCustomIterationsSectionName(SimpleXMLElement $xml_config): ?string
    {
        if ($xml_config->iterations && $xml_config->iterations->section_name) {
            return (string) $xml_config->iterations->section_name;
        }

        return null;
    }

    #[\Override]
    public function getCustomIterationsMilestonesName(SimpleXMLElement $xml_config): ?string
    {
        if ($xml_config->iterations && $xml_config->iterations->milestones_name) {
            return (string) $xml_config->iterations->milestones_name;
        }

        return null;
    }

    /**
     * @throws CannotFindXMLNodeAttributeException
     */
    private function getTargetAttributeValueInXMLNode(SimpleXMLElement $xml_node, string $attribute_name): string
    {
        $node_attributes = $xml_node->attributes();
        if ($node_attributes === null || ! isset($node_attributes[$attribute_name])) {
            throw new CannotFindXMLNodeAttributeException($attribute_name, $xml_node->getName());
        }

        return (string) $node_attributes[$attribute_name];
    }
}
