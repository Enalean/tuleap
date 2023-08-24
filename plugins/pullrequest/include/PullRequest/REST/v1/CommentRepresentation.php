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

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\REST\JsonCast;
use Codendi_HTMLPurifier;
use Tuleap\User\REST\MinimalUserRepresentation;

final class CommentRepresentation
{
    /** @var int */
    public $id;

    /** @var MinimalUserRepresentation */
    public $user;

    /**
     * @var string {@type string}
     */
    public $post_date;

    /** @var string */
    public $content;

    /** @var string */
    public $type;

    /**
     * @var int {@type int}
     */
    public int $parent_id;

    /**
     * @var string {@type string}
     */
    public string $color;
    /**
     * @var string {@type string}
     */
    public string $format;
    /**
     * @var string {@type string}
     */
    public string $post_processed_content;

    private function __construct(private readonly Codendi_HTMLPurifier $purifier, private readonly ContentInterpretor $common_mark_interpreter, int $id, int $project_id, MinimalUserRepresentation $user_representation, int $post_date, string $content, int $parent_id, string $color, string $format)
    {
        $this->id                     = $id;
        $this->user                   = $user_representation;
        $this->post_date              = JsonCast::toDate($post_date);
        $this->content                = $this->getPurifiedContent($project_id, $content);
        $this->type                   = 'comment';
        $this->parent_id              = $parent_id;
        $this->color                  = $color;
        $this->format                 = $format;
        $this->post_processed_content = $this->getPurifiedContentFromHTML($format, $project_id, $content);
    }

    public static function build(Codendi_HTMLPurifier $purifier, ContentInterpretor $common_mark_interpreter, int $id, int $project_id, MinimalUserRepresentation $user_representation, string $color, Comment $comment): self
    {
        return new self($purifier, $common_mark_interpreter, $id, $project_id, $user_representation, $comment->getPostDate(), $comment->getContent(), $comment->getParentId(), $color, $comment->getFormat());
    }

    private function getPurifiedContent(int $project_id, string $content): string
    {
        return $this->purifier->purify($content, Codendi_HTMLPurifier::CONFIG_BASIC, $project_id);
    }

    private function getPurifiedContentFromHTML(string $format, int $project_id, string $content): string
    {
        if ($format === Comment::FORMAT_MARKDOWN) {
            return $this->common_mark_interpreter->getInterpretedContentWithReferences(
                $content,
                $project_id
            );
        }

        return $this->getPurifiedContent($project_id, $content);
    }
}
