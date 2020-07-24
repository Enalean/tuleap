<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class SVNPathsUpdater
{

    public function transformContent($submitted_content)
    {
        $submitted_content_lines = explode(PHP_EOL, $submitted_content);

        $transformed_content = [];
        foreach ($submitted_content_lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $line = '/' . trim($line, '/') . '/';

            $transformed_content[] = $line;
        }

        return implode(PHP_EOL, $transformed_content);
    }
}
