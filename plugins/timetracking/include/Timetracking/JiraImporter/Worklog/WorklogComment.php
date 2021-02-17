<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\JiraImporter\Worklog;

/**
 * @psalm-immutable
 */
class WorklogComment
{
    /**
     * @var string[]
     */
    private $text_paragraphs;

    public function __construct(array $text_paragraphs)
    {
        $this->text_paragraphs = $text_paragraphs;
    }

    public static function buildFromAPIResponse(array $worklog_comment_response): self
    {
        if (
            ! isset($worklog_comment_response['content']) ||
            ! is_array($worklog_comment_response['content'])
        ) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog comment does not have all the expected `content` key."
            );
        }

        $text_paragraphs = [];
        foreach ($worklog_comment_response['content'] as $comment_content) {
            if (
                ! isset($comment_content['type']) ||
                $comment_content['type'] !== 'paragraph' ||
                ! isset($comment_content['content']) ||
                ! is_array($comment_content['content'])
            ) {
                continue;
            }

            foreach ($comment_content['content'] as $comment_content_part) {
                if (! isset($comment_content_part['type']) || $comment_content_part['type'] !== 'text') {
                    continue;
                }

                $text_paragraphs[] = $comment_content_part['text'];
            }
        }

        return new self($text_paragraphs);
    }

    public function getCommentInTextFormat(): string
    {
        return implode(" ", $this->text_paragraphs);
    }
}
