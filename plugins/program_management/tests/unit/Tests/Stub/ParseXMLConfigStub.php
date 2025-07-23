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

use Tuleap\ProgramManagement\Adapter\XML\ParseXMLConfig;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotLoadXMLConfigFileException;

final class ParseXMLConfigStub implements ParseXMLConfig
{
    private bool $is_there_a_config_file;
    private bool $will_parsing_fail = false;

    private function __construct(bool $is_there_a_config_file)
    {
        $this->is_there_a_config_file = $is_there_a_config_file;
    }

    public static function buildWithNoConfigFile(): self
    {
        return new self(false);
    }

    public static function buildWithConfigFile(): self
    {
        return new self(true);
    }

    public function withFailingParsing(): self
    {
        $this->will_parsing_fail = true;
        return $this;
    }

    #[\Override]
    public function isThereAConfigToImport(string $extraction_path): bool
    {
        return $this->is_there_a_config_file;
    }

    #[\Override]
    public function parseConfig(string $extraction_path): \SimpleXMLElement
    {
        if (! $this->is_there_a_config_file || $this->will_parsing_fail) {
            throw new CannotLoadXMLConfigFileException('path/to/xml');
        }

        return new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><configuration/>');
    }
}
