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

namespace Tuleap\PullRequest;

/**
 * @psalm-immutable
 */
class UniDiffLine
{
    public const REMOVED = -1;
    public const KEPT    =  0;
    public const ADDED   =  1;

    private $type;
    private $unidiff_offset;
    private $old_offset;
    private $new_offset;
    private $content;

    public function __construct($type, $unidiff_offset, $old_offset, $new_offset, $content)
    {
        $this->type           = $type;
        $this->unidiff_offset = $unidiff_offset;
        $this->old_offset     = $old_offset;
        $this->new_offset     = $new_offset;
        $this->content        = $content;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUnidiffOffset()
    {
        return $this->unidiff_offset;
    }

    public function getOldOffset()
    {
        return $this->old_offset;
    }

    public function getNewOffset()
    {
        return $this->new_offset;
    }

    public function getContent()
    {
        return $this->content;
    }
}
