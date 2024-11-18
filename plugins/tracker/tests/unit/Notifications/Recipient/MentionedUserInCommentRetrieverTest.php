<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Recipient;

use Tracker_Artifact_Changeset;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class MentionedUserInCommentRetrieverTest extends TestCase
{
    private MentionedUserInCommentRetriever $mentioned_user_in_comment_retriever;

    protected function setUp(): void
    {
        $this->mentioned_user_in_comment_retriever = new MentionedUserInCommentRetriever();
    }

    public static function providerForEmptyUsername(): array
    {
        return [
            'Comment is empty' => [
                '',
            ],
            'No username found' => [
                'Some text',
            ],
        ];
    }

    public static function providerForFoundUsername(): array
    {
        return [
            'Start with a mention' => [
                '@peralta please review',
                ['peralta'],
            ],
            'Start with some text' => [
                'I start with a text @holtr',
                ['holtr'],
            ],
            'With several users' => [
                '@peralta and @santiago please review this report',
                ['peralta' , 'santiago'],
            ],
            'Text with several line' => [
                '
                 text with several lines
                 @peralta
                ',
                ['peralta'],
            ],
            'Text wth username and a mail' => [
                'I start with a text username@example.com and @boylec',
                ['boylec'],
            ],
        ];
    }

    public function testItReturnsAnEmptyUsernamesCollectionIfThereIsNoComment(): void
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getComment')->willReturn(null);
        $result = $this->mentioned_user_in_comment_retriever->getMentionedUsernames($changeset);
        self::assertCount(0, $result->usernames);
    }

    /**
     * @dataProvider providerForEmptyUsername
     */
    public function testItReturnsAnEmptyUsernamesCollection(string $comment): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(15)->withTextComment($comment)->build();
        $result    = $this->mentioned_user_in_comment_retriever->getMentionedUsernames($changeset);
        self::assertCount(0, $result->usernames);
    }

    /**
     * @dataProvider providerForFoundUsername
     * @param list<string> $expected_usernames
     */
    public function testItReturnsTheUsernamesFoundInCommentBody(string $comment, array $expected_usernames): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(15)->withTextComment($comment)->build();
        $result    = $this->mentioned_user_in_comment_retriever->getMentionedUsernames($changeset);
        self::assertEqualsCanonicalizing($expected_usernames, $result->usernames);
    }
}
