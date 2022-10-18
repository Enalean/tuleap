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

namespace Tuleap\OnlyOffice\Open;

final class AllowedFileExtensions
{
    private const EXTENSIONS = [
        'csv',
        'doc',
        'docm',
        'docx',
        'docxf',
        'oform',
        'dot',
        'dotx',
        'epub',
        'htm',
        'html',
        'odp',
        'ods',
        'odt',
        'otp',
        'ots',
        'ott',
        'pdf',
        'pot',
        'potm',
        'potx',
        'pps',
        'ppsm',
        'ppsx',
        'ppt',
        'pptm',
        'pptx',
        'rtf',
        'txt',
        'xls',
        'xlsm',
        'xlsx',
        'xlt',
        'xltm',
        'xltx',
    ];

    private const EXTENSIONS_THAT_CAN_BE_EDITED = [
        'docx',
        'docxf',
        'oform',
        'ppsx',
        'pptx',
        'xlsx',
    ];

    private function __construct()
    {
    }

    public static function isFilenameAllowedToBeOpenInOnlyOffice(string $filename): bool
    {
        return self::isExtensionAllowed(pathinfo($filename, PATHINFO_EXTENSION), self::EXTENSIONS);
    }

    public static function isFilenameAllowedToBeEditedInOnlyOffice(string $filename): bool
    {
        return self::isExtensionAllowed(pathinfo($filename, PATHINFO_EXTENSION), self::EXTENSIONS_THAT_CAN_BE_EDITED);
    }

    /**
     * @param list<string> $allow_list
     */
    private static function isExtensionAllowed(string $extension, array $allow_list): bool
    {
        return in_array(strtolower($extension), $allow_list, true);
    }
}
