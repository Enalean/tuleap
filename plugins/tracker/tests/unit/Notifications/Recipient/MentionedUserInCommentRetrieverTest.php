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

use PFUser;
use Tracker_Artifact_Changeset;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class MentionedUserInCommentRetrieverTest extends TestCase
{
    private MentionedUserInCommentRetriever $mentioned_user_in_comment_retriever;
    private PFUser $peralta;
    private const PERALTA_ID    = 101;
    private const PERALTA_NAME  = 'peralta';
    private const SANTIAGO_ID   = 102;
    private const SANTIAGO_NAME = 'santiago';
    private const HOLT_ID       = 103;
    private const HOLT_NAME     = 'holt';
    private const BOYLE_ID      = 104;
    private const BOYLE_NAME    = 'boyle';

    private PFUser $holt;
    private PFUser $santiago;
    private PFUser $boyle;

    protected function setUp(): void
    {
        $this->peralta  = UserTestBuilder::anActiveUser()->withId(self::PERALTA_ID)->withUserName(self::PERALTA_NAME)->build();
        $this->holt     = UserTestBuilder::anActiveUser()->withId(self::HOLT_ID)->withUserName(self::HOLT_NAME)->build();
        $this->santiago = UserTestBuilder::anActiveUser()->withId(self::SANTIAGO_ID)->withUserName(self::SANTIAGO_NAME)->build();
        $this->boyle    = UserTestBuilder::anActiveUser()->withId(self::BOYLE_ID)->withUserName(self::BOYLE_NAME)->build();

        $this->mentioned_user_in_comment_retriever = new MentionedUserInCommentRetriever(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithId(101))->withUsers(
                [$this->peralta, $this->santiago, $this->boyle, $this->holt]
            )
        );
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

    public function providerForFoundUsername(): array
    {
        $peralta  = UserTestBuilder::anActiveUser()->withId(self::PERALTA_ID)->withUserName(self::PERALTA_NAME)->build();
        $holt     = UserTestBuilder::anActiveUser()->withId(self::HOLT_ID)->withUserName(self::HOLT_NAME)->build();
        $santiago = UserTestBuilder::anActiveUser()->withId(self::SANTIAGO_ID)->withUserName(self::SANTIAGO_NAME)->build();
        $boyle    = UserTestBuilder::anActiveUser()->withId(self::BOYLE_ID)->withUserName(self::BOYLE_NAME)->build();


        return [
            'Start with a mention' => [
                '@peralta please review',
                [$peralta],
            ],
            'Start with some text' => [
                'I start with a text @holt',
                [$holt],
            ],
            'With several users' => [
                '@peralta and @santiago please review this report',
                [$peralta, $santiago],
            ],
            'Text with several line' => [
                '
                 text with several lines
                 @peralta
                ',
                [$peralta],
            ],
            'Text wth username and a mail' => [
                'I start with a text username@example.com and @boyle',
                [$boyle],
            ],
            'Text with inexisting user' => [
                'Inexisting user @linetti but @boyle exist',
                [$boyle],
            ],
        ];
    }

    public function testItReturnsAnEmptyUserCollectionIfThereIsNoComment(): void
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getComment')->willReturn(null);
        $result = $this->mentioned_user_in_comment_retriever->getMentionedUsers($changeset);
        self::assertCount(0, $result->users);
    }

    /**
     * @dataProvider providerForEmptyUsername
     */
    public function testItReturnsAnEmptyUserCollection(string $comment): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(15)->withTextComment($comment)->build();
        $result    = $this->mentioned_user_in_comment_retriever->getMentionedUsers($changeset);
        self::assertCount(0, $result->users);
    }

    /**
     * @dataProvider providerForFoundUsername
     *
     * @param list<\PFUser> $expected_users
     */
    public function testItReturnsTheUsernamesFoundInCommentBody(string $comment, array $expected_users): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(15)->withTextComment($comment)->build();
        $result    = $this->mentioned_user_in_comment_retriever->getMentionedUsers($changeset);

        self::assertEquals($expected_users, $result->users);
    }
}
