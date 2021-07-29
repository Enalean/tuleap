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

use SimpleXMLElement;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUGroups;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindXMLNodeAttributeException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotLoadXMLConfigFileException;

final class ProgramManagementXMLConfigExtractor implements ExtractXMLConfig
{
    private const PROGRAM_MANAGEMENT_CONFIG_XML = 'program-management-config.xml';

    private RetrieveUGroups $ugroup_retriever;

    public function __construct(RetrieveUGroups $ugroup_retriever)
    {
        $this->ugroup_retriever = $ugroup_retriever;
    }

    public function isThereAConfigToImport(string $extraction_path): bool
    {
        $xml_path = $extraction_path . '/' . self::PROGRAM_MANAGEMENT_CONFIG_XML;

        return file_exists($xml_path);
    }

    public function extractConfigForProgram(
        ProgramForAdministrationIdentifier $program_identifier,
        string $extraction_path,
        array $created_trackers_mapping
    ): ProgramManagementXMLConfig {
        $xml_config = $this->getValidXMLConfig($extraction_path);

        return new ProgramManagementXMLConfig(
            $this->getSourceTrackerId($xml_config, $created_trackers_mapping),
            $this->getPlannableTrackersIds($xml_config, $created_trackers_mapping),
            $this->getUgroupsIdsThatCanPrioritize($xml_config, $program_identifier),
            $this->getCustomProgramIncrementsSectionName($xml_config),
            $this->getCustomMilestonesName($xml_config)
        );
    }

    /**
     * @throws CannotLoadXMLConfigFileException
     * @throws \XML_ParseException
     */
    private function getValidXMLConfig(string $extraction_path): SimpleXMLElement
    {
        $xml_path   = $extraction_path . '/' . self::PROGRAM_MANAGEMENT_CONFIG_XML;
        $xml_config = simplexml_load_string(file_get_contents($xml_path));
        if (! $xml_config) {
            throw new CannotLoadXMLConfigFileException($xml_path);
        }

        $xml_validator = new \XML_RNGValidator();
        $rng_path      = dirname(__DIR__, 3) . '/resources/program_management.rng';

        $xml_validator->validate($xml_config, $rng_path);

        return $xml_config;
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

    /**
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindSourceTrackerUsingXmlReference
     */
    private function getSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): int
    {
        $source_tracker_ref = $this->getTargetAttributeValueInXMLNode($xml_config->configuration->source_tracker, 'REF');

        if (! isset($created_trackers_mapping[$source_tracker_ref])) {
            throw new CannotFindSourceTrackerUsingXmlReference($source_tracker_ref);
        }

        return (int) $created_trackers_mapping[$source_tracker_ref];
    }

    /**
     * @return int[]
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindPlannableTrackerInMappingException
     */
    private function getPlannableTrackersIds(SimpleXMLElement $xml_config, array $created_trackers_mapping): array
    {
        $trackers_ids = [];
        foreach ($xml_config->configuration->plannable_trackers->children() as $plannable_tracker) {
            $tracker_ref = $this->getTargetAttributeValueInXMLNode($plannable_tracker, "REF");

            if (! isset($created_trackers_mapping[$tracker_ref])) {
                throw new CannotFindPlannableTrackerInMappingException($tracker_ref);
            }

            $trackers_ids[] = (int) $created_trackers_mapping[$tracker_ref];
        }
        return $trackers_ids;
    }

    /**
     * @return string[]
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindUserGroupInProjectException
     */
    private function getUgroupsIdsThatCanPrioritize(SimpleXMLElement $xml_config, ProgramForAdministrationIdentifier $program_identifier): array
    {
        $ugroups_that_can_prioritize = [];

        foreach ($xml_config->configuration->can_prioritize->children() as $ugroup) {
            $ugroup_name    = $this->getTargetAttributeValueInXMLNode($ugroup, "ugroup_name");
            $project_ugroup = $this->ugroup_retriever->getUGroupByNameInProgram($program_identifier, $ugroup_name);

            if (! $project_ugroup) {
                throw new CannotFindUserGroupInProjectException($ugroup_name);
            }

            $ugroups_that_can_prioritize[] = implode('_', [$program_identifier->id, $project_ugroup->getId()]);
        }

        return $ugroups_that_can_prioritize;
    }

    private function getCustomProgramIncrementsSectionName(SimpleXMLElement $xml_config): ?string
    {
        if ($xml_config->customisation && $xml_config->customisation->program_increments_section_name) {
            return (string) $xml_config->customisation->program_increments_section_name;
        }

        return null;
    }

    private function getCustomMilestonesName(SimpleXMLElement $xml_config): ?string
    {
        if ($xml_config->customisation && $xml_config->customisation->program_increments_milestones_name) {
            return (string) $xml_config->customisation->program_increments_milestones_name;
        }

        return null;
    }
}
