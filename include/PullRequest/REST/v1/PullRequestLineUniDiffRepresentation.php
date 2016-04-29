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

namespace Tuleap\PullRequest\REST\v1;

class PullRequestLineUniDiffRepresentation
{
    /**
     * @var int {@type int}
     */
    public $unidiff_offset;

    /**
     * @var int {@type int}
     */
    public $old_offset;

    /**
     * @var int {@type int}
     */
    public $new_offset;

    /**
     * @var string {@type string}
     */
    public $content;

    public function __construct($unidiff_offset, $old_offset, $new_offset, $content)
    {
        $this->unidiff_offset = $unidiff_offset;
        $this->old_offset     = $old_offset;
        $this->new_offset     = $new_offset;
        $this->content        = $content;
    }
}
