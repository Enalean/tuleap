<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\ApprovalTable;

use DateTimeImmutable;
use Docman_ApprovalTableReviewerFactory;
use Docman_Item;
use Docman_NotificationsManager;
use EventManager;
use Lcobucci\Clock\FrozenClock;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Test\Builders\ApprovalReviewerTestBuilder;
use Tuleap\Docman\Test\Builders\ApprovalTableTestBuilder;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class ApprovalTableReviewUpdaterTest extends TestCase
{
    private const int REVIEW_DATE = 1234567890;

    private ApprovalTableReviewUpdater $updater;
    private Docman_ApprovalTableReviewerFactory&MockObject $reviewer_factory;
    private Docman_NotificationsManager&MockObject $notifications_manager;

    #[Override]
    protected function setUp(): void
    {
        $this->reviewer_factory      = $this->createMock(Docman_ApprovalTableReviewerFactory::class);
        $this->notifications_manager = $this->createMock(Docman_NotificationsManager::class);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');

        $this->updater = new ApprovalTableReviewUpdater(
            $this->reviewer_factory,
            $this->notifications_manager,
            $event_manager,
            new FrozenClock(new DateTimeImmutable()->setTimestamp(self::REVIEW_DATE)),
        );
    }

    public function testItThrowsIfTableIsNotEnabled(): void
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->updater->update(
            new Docman_Item(),
            UserTestBuilder::buildWithDefaults(),
            ApprovalTableTestBuilder::anApprovalTable()->withStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED)->build(),
            new ApprovalTableReviewPutRepresentation('', '', false),
        );
    }

    public function testItThrowsIfUserNotReviewer(): void
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->reviewer_factory->method('isReviewer')->with(102)->willReturn(false);

        $this->updater->update(
            new Docman_Item(),
            UserTestBuilder::buildWithId(102),
            ApprovalTableTestBuilder::anApprovalTable()->withStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED)->build(),
            new ApprovalTableReviewPutRepresentation('', '', false),
        );
    }

    public function testItThrowsIfFailedUpdate(): void
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->reviewer_factory->method('isReviewer')->with(102)->willReturn(true);
        $this->reviewer_factory->method('updateReview')->with(
            ApprovalReviewerTestBuilder::aReviewer()
                ->withId(102)
                ->withState(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED)
                ->withComment('please do not submit this')
                ->withVersion(32)
                ->withReviewDate(self::REVIEW_DATE)
                ->build(),
        )->willReturn(false);

        $this->updater->update(
            new Docman_Item(),
            UserTestBuilder::buildWithId(102),
            ApprovalTableTestBuilder::anApprovalTable()
                ->withStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED)
                ->withVersionNumber(32)
                ->buildVersionned(),
            new ApprovalTableReviewPutRepresentation('rejected', 'please do not submit this', false),
        );
    }

    public function testItAddUserToNotifications(): void
    {
        $this->reviewer_factory->method('isReviewer')->with(102)->willReturn(true);
        $this->reviewer_factory->method('updateReview')->with(
            ApprovalReviewerTestBuilder::aReviewer()
                ->withId(102)
                ->withState(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED)
                ->withComment('please do not submit this')
                ->withVersion(32)
                ->withReviewDate(self::REVIEW_DATE)
                ->build(),
        )->willReturn(true);

        $this->notifications_manager->method('userExists')->willReturn(false);
        $this->notifications_manager->expects($this->once())->method('add')->with(102, 123);

        $this->updater->update(
            new Docman_Item(['item_id' => 123]),
            UserTestBuilder::buildWithId(102),
            ApprovalTableTestBuilder::anApprovalTable()
                ->withStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED)
                ->withVersionNumber(32)
                ->buildVersionned(),
            new ApprovalTableReviewPutRepresentation('rejected', 'please do not submit this', true),
        );
    }

    public function testItRemoveUserFromNotifications(): void
    {
        $this->reviewer_factory->method('isReviewer')->with(102)->willReturn(true);
        $this->reviewer_factory->method('updateReview')->with(
            ApprovalReviewerTestBuilder::aReviewer()
                ->withId(102)
                ->withState(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET)
                ->withComment('')
                ->withVersion(null)
                ->withReviewDate(null)
                ->build(),
        )->willReturn(true);

        $this->notifications_manager->method('userExists')->willReturn(true);
        $this->notifications_manager->expects($this->once())->method('removeUser')->with(102, 123);

        $this->updater->update(
            new Docman_Item(['item_id' => 123]),
            UserTestBuilder::buildWithId(102),
            ApprovalTableTestBuilder::anApprovalTable()
                ->withStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED)
                ->withVersionNumber(32)
                ->buildVersionned(),
            new ApprovalTableReviewPutRepresentation('not_yet', '', false),
        );
    }
}
