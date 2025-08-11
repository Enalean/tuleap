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

namespace Project\XML\Export;

use Tuleap\Project\XML\Export\ExportOptions;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ExportOptionsTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\TestWith(['', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['all', true])]
    #[\PHPUnit\Framework\Attributes\TestWith(['structure', false])]
    public function testShouldExportAllData(string $mode, bool $expected): void
    {
        $options = new ExportOptions($mode, false, []);
        self::assertEquals($expected, $options->shouldExportAllData());
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['all', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['structure', true])]
    public function testShouldExportStructureOnly(string $mode, bool $expected): void
    {
        $options = new ExportOptions($mode, false, []);
        self::assertEquals($expected, $options->shouldExportStructureOnly());
    }

    #[\PHPUnit\Framework\Attributes\TestWith([[], null])]
    #[\PHPUnit\Framework\Attributes\TestWith([['whatever' => 10], null])]
    #[\PHPUnit\Framework\Attributes\TestWith([['tracker_id' => 10], 10])]
    public function testGetExtraOption(array $extra_options, ?int $expected): void
    {
        $options = new ExportOptions('', false, $extra_options);
        self::assertEquals($expected, $options->getExtraOption('tracker_id'));
    }
}
