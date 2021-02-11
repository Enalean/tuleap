<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException;

class CommentRepresentationBuilder
{
    /**
     * @var ContentInterpretor
     */
    private $interpreter;

    public function __construct(ContentInterpretor $interpreter)
    {
        $this->interpreter = $interpreter;
    }

    /**
     * @throws InvalidCommentFormatException
     */
    public function buildRepresentation(\Tracker_Artifact_Changeset_Comment $comment): CommentRepresentation
    {
        $format = $comment->bodyFormat;
        if (
            $format === \Tracker_Artifact_Changeset_Comment::HTML_COMMENT
            || $format === \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        ) {
            return new HTMLOrTextCommentRepresentation($comment->body, $comment->getPurifiedBodyForHTML(), $format);
        }
        if ($format === \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT) {
            $interpreted = $this->interpreter->getInterpretedContentWithReferences(
                $comment->body,
                (int) $comment->changeset->getArtifact()->getTracker()->getGroupId()
            );
            return new CommonMarkCommentRepresentation($interpreted, $comment->body);
        }

        throw new InvalidCommentFormatException($format);
    }
}
