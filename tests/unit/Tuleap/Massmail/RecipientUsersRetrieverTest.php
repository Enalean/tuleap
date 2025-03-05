<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Massmail;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RecipientUsersRetrieverTest extends TestCase
{
    private RecipientUsersRetriever $retriever;
    /**
     * @var RecipientUserDAO&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao       = $this->createMock(RecipientUserDAO::class);
        $this->retriever = new RecipientUsersRetriever($this->dao);
    }

    public function testItRetrievesRecipientsWithAdditionalCommunityMailingsSubscribers(): void
    {
        $recipient_row_01 = ['email' => 'a@example.com'];
        $recipient_row_02 = ['email' => 'B@example.com'];

        $this->dao->expects(self::once())
            ->method('searchRecipientsWithAdditionalCommunityMailingsSubscribers')
            ->willReturn([
                $recipient_row_01,
                $recipient_row_02,
            ]);

        self::assertEqualsCanonicalizing(
            [
                RecipientUser::buildFromArray($recipient_row_01),
                RecipientUser::buildFromArray($recipient_row_02),
            ],
            $this->retriever->getRecipientUsers('comm'),
        );
    }

    public function testItRetrievesRecipientsWithSiteUpdatesSubscribers(): void
    {
        $recipient_row_01 = ['email' => 'a@example.com'];
        $recipient_row_02 = ['email' => 'B@example.com'];

        $this->dao->expects(self::once())
            ->method('searchRecipientsWithSiteUpdatesSubscribers')
            ->willReturn([
                $recipient_row_01,
                $recipient_row_02,
            ]);

        self::assertEqualsCanonicalizing(
            [
                RecipientUser::buildFromArray($recipient_row_01),
                RecipientUser::buildFromArray($recipient_row_02),
            ],
            $this->retriever->getRecipientUsers('sf'),
        );
    }

    public function testItRetrievesRecipientsAllUsers(): void
    {
        $recipient_row_01 = ['email' => 'a@example.com'];
        $recipient_row_02 = ['email' => 'B@example.com'];

        $this->dao->expects(self::once())
            ->method('searchRecipientsAllUsers')
            ->willReturn([
                $recipient_row_01,
                $recipient_row_02,
            ]);

        self::assertEqualsCanonicalizing(
            [
                RecipientUser::buildFromArray($recipient_row_01),
                RecipientUser::buildFromArray($recipient_row_02),
            ],
            $this->retriever->getRecipientUsers('all'),
        );
    }

    public function testItRetrievesRecipientsWithProjectAdministrators(): void
    {
        $recipient_row_01 = ['email' => 'a@example.com'];
        $recipient_row_02 = ['email' => 'B@example.com'];

        $this->dao->expects(self::once())
            ->method('searchRecipientsWithProjectAdministrators')
            ->willReturn([
                $recipient_row_01,
                $recipient_row_02,
            ]);

        self::assertEqualsCanonicalizing(
            [
                RecipientUser::buildFromArray($recipient_row_01),
                RecipientUser::buildFromArray($recipient_row_02),
            ],
            $this->retriever->getRecipientUsers('admin'),
        );
    }

    public function testItRetrievesRecipientsWithPlatformAdministrators(): void
    {
        $recipient_row_01 = ['email' => 'a@example.com'];
        $recipient_row_02 = ['email' => 'B@example.com'];

        $this->dao->expects(self::once())
            ->method('searchRecipientsWithPlatformAdministrators')
            ->willReturn([
                $recipient_row_01,
                $recipient_row_02,
            ]);

        self::assertEqualsCanonicalizing(
            [
                RecipientUser::buildFromArray($recipient_row_01),
                RecipientUser::buildFromArray($recipient_row_02),
            ],
            $this->retriever->getRecipientUsers('sfadmin'),
        );
    }

    public function testItRetrievesRecipientsWithProjectDevelopers(): void
    {
        $recipient_row_01 = ['email' => 'a@example.com'];
        $recipient_row_02 = ['email' => 'B@example.com'];

        $this->dao->expects(self::once())
            ->method('searchRecipientsWithProjectDevelopers')
            ->willReturn([
                $recipient_row_01,
                $recipient_row_02,
            ]);

        self::assertEqualsCanonicalizing(
            [
                RecipientUser::buildFromArray($recipient_row_01),
                RecipientUser::buildFromArray($recipient_row_02),
            ],
            $this->retriever->getRecipientUsers('devel'),
        );
    }

    public function testItReturnsEmptyRecipientsWhenUnknown(): void
    {
        self::assertEmpty($this->retriever->getRecipientUsers('whatever'));
    }
}
