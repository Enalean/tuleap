<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\ShortStat;

/**
 * @psalm-immutable
 */
class PullRequestShortStatRepresentation
{

    /** @var int */
    public $files_changed;

    /** @var int */
    public $lines_added;

    /** @var int */
    public $lines_removed;

    public function __construct(ShortStat $short_stat)
    {
        $this->files_changed = $short_stat->getFilesChangedNumber();
        $this->lines_added   = $short_stat->getLinesAddedNumber();
        $this->lines_removed = $short_stat->getLinesRemovedNumber();
    }
}
