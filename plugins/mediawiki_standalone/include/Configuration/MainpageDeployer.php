<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

final class MainpageDeployer
{
    public function __construct(private string $path_setting_directory)
    {
    }

    public function deployMainPages(): void
    {
        foreach (glob(__DIR__ . '/Mainpage/*.html') as $filepath) {
            $current_lang = basename($filepath, '.html');

            $folder_current_lang = $this->path_setting_directory . '/additional-packages/mediawiki-content/' . $current_lang;
            if (! is_dir($folder_current_lang)) {
                mkdir($folder_current_lang, 0777, true);
            }

            $mainpage_current_lang_path = $folder_current_lang . '/mainpage.html';
            if (! is_file($mainpage_current_lang_path)) {
                @unlink($mainpage_current_lang_path);
                @symlink($filepath, $mainpage_current_lang_path);
            }
        }
    }
}
