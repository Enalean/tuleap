<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact\Changeset\Comment;

use Tuleap\Project\REST\MinimalUserGroupRepresentation;

/**
 * @psalm-immutable
 */
final class HTMLOrTextCommentRepresentation implements CommentRepresentation
{
    /**
     * @var string Contents of the comment
     */
    public $body = '';

    /**
     * @var string Contents of the comment with interpreted cross-references
     */
    public $post_processed_body = '';

    /**
     * @var string Type of the comment (text|html)
     */
    public $format;

    /**
     * @var MinimalUserGroupRepresentation[] | null
     */
    public $ugroups;

    /**
     * @param MinimalUserGroupRepresentation[]|null $ugroups
     */
    public function __construct(string $body, string $post_processed_body, string $format, ?array $ugroups)
    {
        $this->body                = $body;
        $this->post_processed_body = $post_processed_body;
        $this->format              = $format;
        $this->ugroups             = $ugroups;
    }
}
