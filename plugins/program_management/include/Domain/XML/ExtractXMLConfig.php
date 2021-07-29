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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindXMLNodeAttributeException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotLoadXMLConfigFileException;

interface ExtractXMLConfig
{
    public function isThereAConfigToImport(string $extraction_path): bool;

    /**
     * @throws \XML_ParseException
     * @throws CannotLoadXMLConfigFileException
     * @throws CannotFindXMLNodeAttributeException
     * @throws CannotFindPlannableTrackerInMappingException
     * @throws CannotFindUserGroupInProjectException
     * @throws CannotFindSourceTrackerUsingXmlReference
     */
    public function extractConfigForProgram(
        ProgramForAdministrationIdentifier $program_identifier,
        string $extraction_path,
        array $created_trackers_mapping
    ): ProgramManagementXMLConfig;
}
