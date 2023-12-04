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

/**
 * @psalm-immutable
 */
final class CommentPOSTRepresentation
{
    public string $content;
    /**
     * @var int | null $parent_id {@required false}
     */
    public ?int $parent_id = 0;
    /**
     * @var string | null $format {@required false} {@choice text,commonmark}
     */
    public ?string $format = null;

    public function __construct(
        string $content,
        ?string $format,
        ?int $parent_id,
    ) {
        $this->content   = $content;
        $this->format    = $format;
        $this->parent_id = $parent_id;
    }
}
