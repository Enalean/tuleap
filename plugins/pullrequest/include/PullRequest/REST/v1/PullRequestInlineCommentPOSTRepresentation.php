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

/**
 * @psalm-immutable
 */
class PullRequestInlineCommentPOSTRepresentation
{
    /**
     * @var string {@type string}
     */
    public $content;

    /**
     * @var string | null {@type string} {@required false} {@choice text,commonmark}
     */
    public $format;

    /**
     * @var string {@type string}
     */
    public $file_path;

    /**
     * @var int {@type int}
     */
    public $unidiff_offset;

    /**
     * @var string {@type string}
     */
    public $position;
    /**
     * @var int | null {@type int} {@required false}
     */
    public int|null $parent_id = 0;

    private function __construct(
        string $content,
        string $file_path,
        int $unidiff_offset,
        string $position,
        ?int $parent_id,
        ?string $format,
    ) {
        $this->content        = $content;
        $this->file_path      = $file_path;
        $this->unidiff_offset = $unidiff_offset;
        $this->position       = $position;
        $this->parent_id      = $parent_id;
        $this->format         = $format;
    }

    public static function build(
        string $content,
        string $file_path,
        int $unidiff_offset,
        string $position,
        ?int $parent_id,
        ?string $format,
    ): self {
        return new self(
            $content,
            $file_path,
            $unidiff_offset,
            $position,
            $parent_id,
            $format
        );
    }
}
