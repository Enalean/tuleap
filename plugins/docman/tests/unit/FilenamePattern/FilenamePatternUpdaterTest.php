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

namespace Tuleap\Docman\FilenamePattern;

use Tuleap\Docman\Tests\Stub\SettingsDAOStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilenamePatternUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FilenamePatternUpdater $filename_pattern_updater;
    private SettingsDAOStub $save_filename_counter;

    #[\Override]
    public function setUp(): void
    {
        $this->save_filename_counter = SettingsDAOStub::buildSaveFilenamePatternMethodCounter();

        $this->filename_pattern_updater = new FilenamePatternUpdater($this->save_filename_counter);
    }

    public function testItThrowsAnExceptionWhenThePatternIsNotValid(): void
    {
        $this->expectException(InvalidMinimalPatternException::class);

        $this->filename_pattern_updater->updatePattern(
            101,
            new FilenamePattern('oseille#${VERSION_NAME}', false)
        );
    }

    public function testItThrowsAnExceptionWhenThePatternIsEnforcedButEmpty(): void
    {
        $this->expectException(EnforcedEmptyPatternException::class);

        $this->filename_pattern_updater->updatePattern(
            101,
            new FilenamePattern('', true)
        );
    }

    public function testItSaveTheGivenPattern(): void
    {
        $this->filename_pattern_updater->updatePattern(
            101,
            new FilenamePattern('thune#${ID}', false)
        );
        self::assertEquals(1, $this->save_filename_counter->getCountSaveFilenamePattern());
    }
}
