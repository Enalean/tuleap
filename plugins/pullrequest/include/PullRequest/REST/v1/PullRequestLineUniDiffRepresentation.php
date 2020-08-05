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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\UniDiffLine;

/**
 * @psalm-immutable
 */
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

    public function __construct(UniDiffLine $line, $charset)
    {
        $this->unidiff_offset = $line->getUniDiffOffset();
        $this->old_offset     = $line->getOldOffset();
        $this->new_offset     = $line->getNewOffset();
        $this->content        = $this->getUTF8Content($line, $charset);
    }

    private function getUTF8Content(UniDiffLine $line, $charset)
    {
        if (strtolower($charset) === 'utf-8') {
            return $line->getContent();
        }

        return utf8_encode($line->getContent());
    }
}
