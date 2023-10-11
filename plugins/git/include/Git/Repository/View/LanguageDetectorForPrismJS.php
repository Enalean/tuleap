<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

class LanguageDetectorForPrismJS
{
    private const FILENAMES = [
        'CMakeLists.txt'  => 'cmake',
        'Dockerfile'      => 'dockerfile',
        '.eslintignore'   => 'ignore',
        '.prettierignore' => 'ignore',
        'Cargo.lock'      => 'toml',
        'Makefile'        => 'makefile',
    ];

    private const EXTENSIONS = [
        'js'             => 'javascript',
        'py'             => 'python',
        'rb'             => 'ruby',
        'ps1'            => 'powershell',
        'psm1'           => 'powershell',
        'sh'             => 'bash',
        'bat'            => 'batch',
        'h'              => 'c',
        'tex'            => 'latex',
        'vue'            => 'markup',
        'mkd'            => 'markdown',
        'yml'            => 'yaml',
        'cmake.in'       => 'cmake',
        'tf'             => 'hcl',
        'tfvars'         => 'hcl',
        'tfstate'        => 'json',
        'tfstate.backup' => 'json',
        'rs'             => 'rust',
        'ipynb'          => 'json',
        'rst'            => 'rest',
        'cc'             => 'cpp',
        'hh'             => 'cpp',
    ];

    public function getLanguage(string $filename): string
    {
        if (isset(self::FILENAMES[$filename])) {
            return self::FILENAMES[$filename];
        }

        $path_information = pathinfo($filename);
        if (! isset($path_information['extension'])) {
            return '';
        }

        $extension             = $path_information['extension'];
        $second_extension_part = pathinfo($path_information['filename'], PATHINFO_EXTENSION);

        if ($second_extension_part !== '') {
            $composite_extension = $second_extension_part . '.' . $extension;
            if (isset(self::EXTENSIONS[$composite_extension])) {
                return self::EXTENSIONS[$composite_extension];
            }
        }

        return self::EXTENSIONS[$extension] ?? $extension;
    }
}
