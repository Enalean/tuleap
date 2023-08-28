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

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\REST\JsonCast;
use Codendi_HTMLPurifier;

final class PullRequestInlineCommentRepresentation
{
    /**
     * @var int {@type int}
     */
    public $unidiff_offset;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $user;

    /**
     * @var string {@type string}
     */
    public $post_date;

    /**
     * @var string {@type string}
     */
    public $content;

    /**
     * @var string {@type string}
     */
    public $position;

    /**
     * @var int {@type int}
     */
    public int $parent_id;
    /**
     * @var int {@type int}
     */
    public int $id;
    /**
     * @var string {@type string}
     */
    public string $file_path;
    /**
     * @var string {@type string}
     */
    public string $color;
    /**
     * @var string
     */
    public $post_processed_content;

    private function __construct(private readonly Codendi_HTMLPurifier $purifier, private readonly ContentInterpretor $common_mark_interpreter, int $unidiff_offset, MinimalUserRepresentation $user, int $post_date, string $content, int $project_id, string $position, int $parent_id, int $id, string $file_path, string $color, string $format)
    {
        $this->unidiff_offset         = $unidiff_offset;
        $this->user                   = $user;
        $this->post_date              = JsonCast::toDate($post_date);
        $this->content                = $this->getPurifiedContent($content, $project_id);
        $this->position               = $position;
        $this->parent_id              = $parent_id;
        $this->id                     = $id;
        $this->file_path              = $file_path;
        $this->color                  = $color;
        $this->post_processed_content = $this->getPurifiedContentFromHTML($format, $project_id, $content);
    }

    public static function build(Codendi_HTMLPurifier $purifier, ContentInterpretor $common_mark_interpreter, int $unidiff_offset, MinimalUserRepresentation $user, int $post_date, string $content, int $project_id, string $position, int $parent_id, int $id, string $file_path, string $color, string $format): self
    {
        return new self($purifier, $common_mark_interpreter, $unidiff_offset, $user, $post_date, $content, $project_id, $position, $parent_id, $id, $file_path, $color, $format);
    }

    private function getPurifiedContent(string $content, int $project_id): string
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

        return $this->getPurifiedContent($content, $project_id);
    }
}
