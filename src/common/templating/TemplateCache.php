<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Templating;

class TemplateCache implements TemplateCacheInterface
{
    public const CACHE_FOLDER_NAME = 'template_engine';


    public function getPath(): ?string
    {
        return \ForgeConfig::get('codendi_cache_dir') . DIRECTORY_SEPARATOR . self::CACHE_FOLDER_NAME;
    }

    public function invalidate(): void
    {
        $path = $this->getPath();
        if ($path === null) {
            return;
        }

        if (! is_dir($path)) {
            return;
        }

        foreach (new \DirectoryIterator($path) as $file_info) {
            if ($file_info->isFile()) {
                unlink($file_info->getPathname());
            }
        }
    }
}
