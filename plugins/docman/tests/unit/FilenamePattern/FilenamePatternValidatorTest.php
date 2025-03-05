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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilenamePatternValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnTrueWhenThereIsNoPattern(): void
    {
        self::assertTrue(FilenamePatternValidator::isPatternValid(''));
    }

    public function testItReturnTrueWhenThePatternContainsTheTitleVariable(): void
    {
        self::assertTrue(FilenamePatternValidator::isPatternValid('Un gros billet-${TITLE}-${STATUS}'));
    }

    public function testItReturnTrueWhenThePatternContainsTheIdVariable(): void
    {
        self::assertTrue(FilenamePatternValidator::isPatternValid('ter ter-${ID}-${STATUS}'));
    }

    public function testItReturnFalseWhenThePatternDoesNotContainTheMandatoryVariable(): void
    {
        self::assertFalse(FilenamePatternValidator::isPatternValid('some text-${VERSION_NAME}-${STATUS}-text'));
    }
}
