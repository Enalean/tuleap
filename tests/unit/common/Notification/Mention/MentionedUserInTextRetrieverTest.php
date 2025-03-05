<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Notification\Mention;

use PFUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MentionedUserInTextRetrieverTest extends TestCase
{
    private MentionedUserInTextRetriever $mentioned_user_in_text_retriever;
    private const PERALTA_ID    = 101;
    private const PERALTA_NAME  = 'peralta';
    private const SANTIAGO_ID   = 102;
    private const SANTIAGO_NAME = 'santiago';
    private const HOLT_ID       = 103;
    private const HOLT_NAME     = 'holt';
    private const BOYLE_ID      = 104;
    private const BOYLE_NAME    = 'boyle';

    protected function setUp(): void
    {
        $peralta  = UserTestBuilder::anActiveUser()->withId(self::PERALTA_ID)->withUserName(self::PERALTA_NAME)->build();
        $holt     = UserTestBuilder::anActiveUser()->withId(self::HOLT_ID)->withUserName(self::HOLT_NAME)->build();
        $santiago = UserTestBuilder::anActiveUser()->withId(self::SANTIAGO_ID)->withUserName(self::SANTIAGO_NAME)->build();
        $boyle    = UserTestBuilder::anActiveUser()->withId(self::BOYLE_ID)->withUserName(self::BOYLE_NAME)->build();

        $this->mentioned_user_in_text_retriever = new MentionedUserInTextRetriever(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithId(101))->withUsers(
                [$peralta, $santiago, $boyle, $holt]
            )
        );
    }

    public static function providerForEmptyUsername(): array
    {
        return [
            'Comment is empty'  => [
                '',
            ],
            'No username found' => [
                'Some text',
            ],
        ];
    }

    public static function providerForFoundUsername(): array
    {
        $peralta  = UserTestBuilder::anActiveUser()->withId(self::PERALTA_ID)->withUserName(self::PERALTA_NAME)->build();
        $holt     = UserTestBuilder::anActiveUser()->withId(self::HOLT_ID)->withUserName(self::HOLT_NAME)->build();
        $santiago = UserTestBuilder::anActiveUser()->withId(self::SANTIAGO_ID)->withUserName(self::SANTIAGO_NAME)->build();
        $boyle    = UserTestBuilder::anActiveUser()->withId(self::BOYLE_ID)->withUserName(self::BOYLE_NAME)->build();


        return [
            'Start with a mention'         => [
                '@peralta please review',
                [$peralta],
            ],
            'Start with some text'         => [
                'I start with a text @holt',
                [$holt],
            ],
            'With several users'           => [
                '@peralta and @santiago please review this report',
                [$peralta, $santiago],
            ],
            'Text with several line'       => [
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
            'Text with inexisting user'    => [
                'Inexisting user @linetti but @boyle exist',
                [$boyle],
            ],
        ];
    }

    public function testItReturnsAnEmptyUserCollectionIfThereIsNoComment(): void
    {
        $result = $this->mentioned_user_in_text_retriever->getMentionedUsers('');
        self::assertCount(0, $result->users);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForEmptyUsername')]
    public function testItReturnsAnEmptyUserCollection(string $comment): void
    {
        $result = $this->mentioned_user_in_text_retriever->getMentionedUsers($comment);
        self::assertCount(0, $result->users);
    }

    /**
     * @param list<PFUser> $expected_users
     */
    #[DataProvider('providerForFoundUsername')]
    public function testItReturnsTheUsernamesFoundInCommentBody(string $comment, array $expected_users): void
    {
        $result = $this->mentioned_user_in_text_retriever->getMentionedUsers($comment);

        self::assertEquals($expected_users, $result->users);
    }
}
