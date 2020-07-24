<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

class FileUniDiff
{

    private $lines;

    public function __construct()
    {
        $this->lines = [];
    }

    public function addLine($type, $unidiff_offset, $old_offset, $new_offset, $content)
    {
        $this->lines[$unidiff_offset] = new UniDiffLine($type, $unidiff_offset, $old_offset, $new_offset, $content);
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function getLine($unidiff_offset)
    {
        return $this->lines[$unidiff_offset];
    }

    public function getLineFromOldOffset($old_offset)
    {
        foreach ($this->lines as $line) {
            if ($line->getOldOffset() == $old_offset) {
                return $line;
            }
        }
        return null;
    }

    public function getLineFromNewOffset($new_offset)
    {
        foreach ($this->lines as $line) {
            if ($line->getNewOffset() == $new_offset) {
                return $line;
            }
        }
        return null;
    }
}
