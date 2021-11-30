<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class ShortStat
{
    /** @var int */
    private $files_changed;
    /** @var int */
    private $lines_added;
    /** @var int */
    private $lines_removed;

    public function __construct(
        $files_changed,
        $lines_added,
        $lines_removed,
    ) {
        $this->files_changed = $files_changed;
        $this->lines_added   = $lines_added;
        $this->lines_removed = $lines_removed;
    }

    public function getFilesChangedNumber()
    {
        return $this->files_changed;
    }

    public function getLinesAddedNumber()
    {
        return $this->lines_added;
    }

    public function getLinesRemovedNumber()
    {
        return $this->lines_removed;
    }
}
