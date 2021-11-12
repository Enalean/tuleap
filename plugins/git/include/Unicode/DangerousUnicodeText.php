<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Git\Unicode;

final class DangerousUnicodeText
{
    private function __construct()
    {
    }

    public static function getCodePotentiallyDangerousBidirectionalUnicodeTextWarning(string $code): ?string
    {
        if (self::doesCodeContainsPotentiallyDangerousBidirectionalUnicodeText($code)) {
            return dgettext(
                'tuleap-git',
                'This file contains bidirectional Unicode text. It may be interpreted differently than what is shown. ' .
                'This might be used to inject malicious code that looks safe. ' .
                'You should review the file in an editor that reveals hidden Unicode characters.'
            );
        }
        return null;
    }

    private static function doesCodeContainsPotentiallyDangerousBidirectionalUnicodeText(string $code): bool
    {
        $potentially_dangerous_bidirectional_characters = json_decode(
            file_get_contents(__DIR__ . '/../../../../src/common/Code/potentially-dangerous-bidirectional-characters.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($potentially_dangerous_bidirectional_characters as $character) {
            if (str_contains($code, $character)) {
                return true;
            }
        }

        return false;
    }
}
