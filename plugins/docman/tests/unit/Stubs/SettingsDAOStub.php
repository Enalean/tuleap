<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Tests\Stub;

use Tuleap\Docman\FilenamePattern\FilenamePattern;
use Tuleap\Docman\Settings\DAOSettings;

final class SettingsDAOStub implements DAOSettings
{
    private function __construct(private int $count_save_filename_pattern)
    {
        $this->count_save_filename_pattern = 0;
    }

    public static function buildSaveFilenamePatternMethodCounter(): self
    {
        return new self(0);
    }

    #[\Override]
    public function saveFilenamePattern(int $project_id, FilenamePattern $filename_pattern): void
    {
        $this->count_save_filename_pattern++;
    }

    public function getCountSaveFilenamePattern(): int
    {
        return $this->count_save_filename_pattern;
    }
}
