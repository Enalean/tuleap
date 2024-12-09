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

final class CommentFormatIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public static function generateFormats(): iterable
    {
        yield [\Tracker_Artifact_Changeset_Comment::TEXT_COMMENT];
        yield [\Tracker_Artifact_Changeset_Comment::HTML_COMMENT];
        yield [\Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT];
    }

    /**
     * @dataProvider generateFormats
     */
    public function testItBuildsFromFormatString(string $format_string): void
    {
        $format = CommentFormatIdentifier::fromStringWithDefault($format_string);
        self::assertSame($format_string, $format->value);
    }

    public function testItDefaultsInvalidFormatToCommonMark(): void
    {
        $format = CommentFormatIdentifier::fromStringWithDefault('Invalid');
        self::assertSame(CommentFormatIdentifier::COMMONMARK, $format);
    }
}
