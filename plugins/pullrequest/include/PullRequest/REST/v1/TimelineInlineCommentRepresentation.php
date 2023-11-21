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

use Codendi_HTMLPurifier;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\REST\JsonCast;

final class TimelineInlineCommentRepresentation
{
    /**
     * @var string {@type string}
     */
    public $file_path;

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

    public string $raw_content;

    /**
     * @var bool {@type bool}
     */
    public $is_outdated;

    /**
     * @var string {@type string}
     */
    public $type;

    /**
     * @var int {@type int}
     */
    public $parent_id;
    /**
     * @var int {@type int}
     */
    public int $id;
    /**
     * @var string {@type string}
     */
    public string $position;

    /**
     * @var string {@type string}
     */
    public $color;
    public string $post_processed_content;
    public string $format;
    public ?string $last_edition_date;

    private function __construct(
        private readonly Codendi_HTMLPurifier $purifier,
        private readonly ContentInterpretor $common_mark_interpreter,
        string $file_path,
        int $unidiff_offset,
        MinimalUserRepresentation $user,
        int $post_date,
        string $content,
        bool $is_outdated,
        int $project_id,
        int $parent_id,
        int $id,
        string $position,
        string $color,
        string $format,
        ?int $last_edition_date,
    ) {
        $this->file_path              = $file_path;
        $this->unidiff_offset         = $unidiff_offset;
        $this->user                   = $user;
        $this->post_date              = JsonCast::toDate($post_date);
        $this->content                = $this->getPurifiedContent($project_id, $content);
        $this->raw_content            = $content;
        $this->is_outdated            = $is_outdated;
        $this->type                   = InlineCommentRepresentation::TYPE;
        $this->parent_id              = $parent_id;
        $this->id                     = $id;
        $this->position               = $position;
        $this->color                  = $color;
        $this->format                 = $format;
        $this->last_edition_date      = JsonCast::toDate($last_edition_date);
        $this->post_processed_content = $this->getPurifiedContentFromHTML($format, $project_id, $content);
    }

    public static function build(
        Codendi_HTMLPurifier $purifier,
        ContentInterpretor $common_mark_interpreter,
        string $file_path,
        int $unidiff_offset,
        MinimalUserRepresentation $user,
        int $post_date,
        string $content,
        bool $is_outdated,
        int $project_id,
        int $parent_id,
        int $id,
        string $position,
        string $color,
        string $format,
        ?int $last_edition_date,
    ): self {
        return new self(
            $purifier,
            $common_mark_interpreter,
            $file_path,
            $unidiff_offset,
            $user,
            $post_date,
            $content,
            $is_outdated,
            $project_id,
            $parent_id,
            $id,
            $position,
            $color,
            $format,
            $last_edition_date
        );
    }

    private function getPurifiedContent(int $project_id, string $content): string
    {
        return $this->purifier->purify($content, Codendi_HTMLPurifier::CONFIG_BASIC, $project_id);
    }

    private function getPurifiedContentFromHTML(string $format, int $project_id, string $content): string
    {
        if ($format === TimelineComment::FORMAT_MARKDOWN) {
            return $this->common_mark_interpreter->getInterpretedContentWithReferences(
                $content,
                $project_id,
            );
        }

        return $this->getPurifiedContent($project_id, $content);
    }
}
