<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\FilenamePattern;

use Tuleap\Docman\Settings\SearchFilenamePatternInSettings;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FilenamePatternRetrieverTest extends TestCase
{
    public function testGetPattern(): void
    {
        $retriever = new FilenamePatternRetriever(new class implements SearchFilenamePatternInSettings {
            public function searchFileNamePatternFromProjectId(int $project_id): ?array
            {
                return ['filename_pattern' => 'stuff', 'is_filename_pattern_enforced' => true];
            }
        });

        $filename_pattern = $retriever->getPattern(102);
        self::assertEquals('stuff', $filename_pattern->getPattern());
        self::assertTrue($filename_pattern->isEnforced());
    }

    public function testGetPatternWhenNoEntriesInDb(): void
    {
        $retriever = new FilenamePatternRetriever(new class implements SearchFilenamePatternInSettings {
            public function searchFileNamePatternFromProjectId(int $project_id): ?array
            {
                return null;
            }
        });

        $filename_pattern = $retriever->getPattern(102);
        self::assertEquals('', $filename_pattern->getPattern());
        self::assertFalse($filename_pattern->isEnforced());
    }
}
