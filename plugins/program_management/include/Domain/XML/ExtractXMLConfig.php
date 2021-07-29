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

namespace Tuleap\ProgramManagement\Domain\XML;

use SimpleXMLElement;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindXMLNodeAttributeException;

interface ExtractXMLConfig
{
    /**
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindSourceTrackerUsingXmlReference
     */
    public function getSourceTrackerId(SimpleXMLElement $xml_config, array $created_trackers_mapping): int;

    /**
     * @return int[]
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindPlannableTrackerInMappingException
     */
    public function getPlannableTrackersIds(SimpleXMLElement $xml_config, array $created_trackers_mapping): array;

    /**
     * @return string[]
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindUserGroupInProjectException
     */
    public function getUgroupsIdsThatCanPrioritize(SimpleXMLElement $xml_config, ProgramForAdministrationIdentifier $program_identifier): array;

    public function getCustomProgramIncrementsSectionName(SimpleXMLElement $xml_config): ?string;

    public function getCustomMilestonesName(SimpleXMLElement $xml_config): ?string;
}
