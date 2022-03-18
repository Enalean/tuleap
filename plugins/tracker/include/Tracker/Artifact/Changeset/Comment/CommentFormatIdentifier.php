<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Comment;

/**
 * I identify changeset comments' format.
 * @psalm-immutable
 */
final class CommentFormatIdentifier
{
    private function __construct(private string $format)
    {
    }

    public static function buildText(): self
    {
        return new self(\Tracker_Artifact_Changeset_Comment::TEXT_COMMENT);
    }

    public static function buildHTML(): self
    {
        return new self(\Tracker_Artifact_Changeset_Comment::HTML_COMMENT);
    }

    public static function buildCommonMark(): self
    {
        return new self(\Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT);
    }

    public static function fromFormatString(string $format): self
    {
        return new self(\Tracker_Artifact_Changeset_Comment::checkCommentFormat($format));
    }

    public function __toString(): string
    {
        return $this->format;
    }

    public function isHTML(): bool
    {
        return $this->format === \Tracker_Artifact_Changeset_Comment::HTML_COMMENT;
    }
}
