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

namespace Tuleap\ProgramManagement\Adapter\XML;

use SimpleXMLElement;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindXMLNodeAttributeException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

interface ExtractXMLConfig
{
    /**
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindSourceTrackerUsingXmlReference
     */
    public function getIncrementsSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): int;

    /**
     * @return int[]
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindPlannableTrackerInMappingException
     */
    public function getIncrementsPlannableTrackersIds(SimpleXMLElement $xml_config, array $created_trackers_mapping): array;

    /**
     * @return string[]
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindUserGroupInProjectException
     */
    public function getUgroupsIdsThatCanPrioritizeIncrements(SimpleXMLElement $xml_config, ProgramForAdministrationIdentifier $program_identifier): array;

    public function getCustomProgramIncrementsSectionName(SimpleXMLElement $xml_config): ?string;

    public function getCustomProgramIncrementsMilestonesName(SimpleXMLElement $xml_config): ?string;

    /**
     * @throws CannotFindSourceTrackerUsingXmlReference
     * @throws CannotFindXMLNodeAttributeException
     */
    public function getIterationsSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): ?int;

    public function getCustomIterationsSectionName(SimpleXMLElement $xml_config): ?string;

    public function getCustomIterationsMilestonesName(SimpleXMLElement $xml_config): ?string;
}
