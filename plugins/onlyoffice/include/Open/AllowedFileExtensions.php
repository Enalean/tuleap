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

class AllowedFileExtensions
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

    public static function isFilenameAllowedToBeOpenInOnlyOffice(string $filename): bool
    {
        return in_array(pathinfo($filename, PATHINFO_EXTENSION), self::EXTENSIONS, true);
    }
}
