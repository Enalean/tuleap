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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotLoadXMLConfigFileException;
use Tuleap\ProgramManagement\Domain\XML\ExtractXMLConfig;
use Tuleap\ProgramManagement\Domain\XML\ProgramManagementXMLConfig;

class ExtractXMLConfigStub implements ExtractXMLConfig
{
    private ?ProgramManagementXMLConfig $xml_config;

    private bool $will_extraction_fail = false;

    private function __construct(?ProgramManagementXMLConfig $xml_config)
    {
        $this->xml_config = $xml_config;
    }

    public static function buildWithNoConfigToImport(): self
    {
        return new self(null);
    }

    public static function buildWithConfigToImport(ProgramManagementXMLConfig $xml_config): self
    {
        return new self($xml_config);
    }

    public function withFailingExtraction(): self
    {
        $this->will_extraction_fail = true;
        return $this;
    }

    public function isThereAConfigToImport(string $extraction_path): bool
    {
        return $this->xml_config !== null;
    }

    public function extractConfigForProgram(ProgramForAdministrationIdentifier $program_identifier, string $extraction_path, array $created_trackers_mapping): ProgramManagementXMLConfig
    {
        if ($this->will_extraction_fail) {
            throw new CannotLoadXMLConfigFileException($extraction_path);
        }

        return $this->xml_config;
    }
}
