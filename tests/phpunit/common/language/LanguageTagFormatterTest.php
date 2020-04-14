<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\language;

use PHPUnit\Framework\TestCase;

final class LanguageTagFormatterTest extends TestCase
{
    /**
     * @dataProvider dataProviderLanguageTags
     */
    public function testFormatAsRFC5646LanguageTag($input, $expected_output): void
    {
        $this->assertSame($expected_output, LanguageTagFormatter::formatAsRFC5646LanguageTag($input));
    }

    public function dataProviderLanguageTags(): array
    {
        return [
            ['en_US', 'en-US'],
            ['fr_FR', 'fr-FR'],
            ['ja_JP', 'ja-JP']
        ];
    }
}
