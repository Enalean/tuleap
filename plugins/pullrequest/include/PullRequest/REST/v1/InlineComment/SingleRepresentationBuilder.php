<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\InlineComment;

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\Comment\CommentContent;
use Tuleap\User\REST\MinimalUserRepresentation;

final class SingleRepresentationBuilder
{
    public function __construct(
        private readonly \Codendi_HTMLPurifier $purifier,
        private readonly ContentInterpretor $commonmark_interpreter,
    ) {
    }

    public function build(
        int $project_id,
        MinimalUserRepresentation $user_representation,
        InlineComment $comment,
    ): InlineCommentRepresentation {
        $comment_content = new CommentContent(
            $this->getPurifiedContent($project_id, $comment->getContent()),
            $comment->getContent(),
            $this->getPurifiedContentFromHTML($comment->getFormat(), $project_id, $comment->getContent())
        );
        return new InlineCommentRepresentation(
            $comment,
            $comment_content,
            $user_representation
        );
    }

    private function getPurifiedContent(int $project_id, string $content): string
    {
        return $this->purifier->purify($content, \Codendi_HTMLPurifier::CONFIG_BASIC, $project_id);
    }

    private function getPurifiedContentFromHTML(string $format, int $project_id, string $content): string
    {
        if ($format === TimelineComment::FORMAT_MARKDOWN) {
            return $this->commonmark_interpreter->getInterpretedContentWithReferences(
                $content,
                $project_id
            );
        }

        return $this->getPurifiedContent($project_id, $content);
    }
}
