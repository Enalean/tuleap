<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
final class CommonMarkCommentRepresentation implements CommentRepresentation
{
    /**
     * @var string Comment contents rendered in HTML with interpreted cross-references.
     */
    public $body;

    /**
     * @var string Duplicate of $body. It exists to offer compatibility with HTMLOrTextCommentRepresentation.
     *             This way, comments in all formats have a post_processed_body property.
     */
    public $post_processed_body;

    /**
     * @var string Type of the comment. It is advertised as HTML to avoid introducing breaking changes in the REST API.
     */
    public $format = 'html';

    /**
     * @var string Source CommonMark contents of the comment
     */
    public $commonmark;

    /**
     * @var MinimalUserGroupRepresentation[] | null
     */
    public $ugroups;

    /**
     * @param MinimalUserGroupRepresentation[]|null $ugroups
     */
    public function __construct(string $body, string $commonmark, ?array $ugroups)
    {
        $this->body                = $body;
        $this->post_processed_body = $body;
        $this->commonmark          = $commonmark;
        $this->ugroups             = $ugroups;
    }
}
