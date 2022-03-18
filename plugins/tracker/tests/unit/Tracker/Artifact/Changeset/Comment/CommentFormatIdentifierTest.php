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

namespace Tracker\Artifact\Changeset\Comment;

use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;

final class CommentFormatIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsTextFormat(): void
    {
        $format = CommentFormatIdentifier::buildText();
        self::assertFalse($format->isHTML());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::TEXT_COMMENT, (string) $format);
    }

    public function testItBuildsHTMLFormat(): void
    {
        $format = CommentFormatIdentifier::buildHTML();
        self::assertTrue($format->isHTML());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::HTML_COMMENT, (string) $format);
    }

    public function testItBuildsCommonMarkFormat(): void
    {
        $format = CommentFormatIdentifier::buildCommonMark();
        self::assertFalse($format->isHTML());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT, (string) $format);
    }

    public function dataProviderFormat(): array
    {
        return [
            'Text'       => [\Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,],
            'HTML'       => [\Tracker_Artifact_Changeset_Comment::HTML_COMMENT],
            'CommonMark' => [\Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT],
        ];
    }

    /**
     * @dataProvider dataProviderFormat
     */
    public function testItBuildsFromFormatString(string $format_string): void
    {
        $format = CommentFormatIdentifier::fromFormatString($format_string);
        self::assertSame($format_string, (string) $format);
    }

    public function testItDefaultsInvalidFormatToCommonMark(): void
    {
        $format = CommentFormatIdentifier::fromFormatString('Invalid');
        self::assertFalse($format->isHTML());
        self::assertSame(\Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT, (string) $format);
    }
}
