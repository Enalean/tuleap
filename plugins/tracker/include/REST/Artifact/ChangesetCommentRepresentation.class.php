<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\REST;

use Tracker_Artifact_Changeset_Comment;

/**
 * @psalm-immutable
 */
class ChangesetCommentRepresentation
{

    /**
     * @var string Content of the comment {@required false}
     */
    public $body = '';

    /**
     * @var string Content of the comment with interpreted cross-references {@required false}
     */
    public $post_processed_body = '';

    /**
     * @var string Type of the comment (text|html)
     */
    public $format;

    private function __construct(string $body, string $post_processed_body, string $format)
    {
        $this->body                = $body;
        $this->post_processed_body = $post_processed_body;
        $this->format              = $format;
    }

    public static function build(Tracker_Artifact_Changeset_Comment $comment): self
    {
        return new self(
            $comment->body,
            $comment->getPurifiedBodyForHTML(),
            $comment->bodyFormat
        );
    }
}
