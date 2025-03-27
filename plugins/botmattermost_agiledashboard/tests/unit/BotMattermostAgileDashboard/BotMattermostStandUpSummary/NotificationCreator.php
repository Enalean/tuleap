<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary;

use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\Bot\BotValidityChecker;
use Tuleap\BotMattermost\Exception\BotCannotBeUsedInProjectException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotCreateBotNotificationException;
use Tuleap\Test\PHPUnit\TestCase;

final class NotificationCreatorTest extends TestCase
{
    private NotificationCreator $creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Dao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(Dao::class);

        $this->creator = new NotificationCreator(
            $this->dao,
            new BotValidityChecker()
        );
    }

    public function testItThrowsAnExceptionIfBotDoesNotBelongToProject(): void
    {
        $bot = new Bot(1, 'bot', '', '', 102);

        $this->dao->expects(self::never())
            ->method('createNotification');

        $this->expectException(BotCannotBeUsedInProjectException::class);

        $this->creator->createNotification(
            $bot,
            101,
            ['chan01'],
            '08:00:00'
        );
    }

    public function testItThrowsAnExceptionIfNotificationCannotBeSaved(): void
    {
        $bot = new Bot(1, 'bot', '', '', null);

        $this->dao->expects($this->once())
            ->method('createNotification')
            ->willReturn(false);

        $this->expectException(CannotCreateBotNotificationException::class);

        $this->creator->createNotification(
            $bot,
            101,
            ['chan01'],
            '08:00:00'
        );
    }

    public function testItDoesNotThrowExceptionIfNotificationIsSaved(): void
    {
        $bot = new Bot(1, 'bot', '', '', null);

        $this->dao->expects($this->once())
            ->method('createNotification')
            ->willReturn(true);

        $this->creator->createNotification(
            $bot,
            101,
            ['chan01'],
            '08:00:00'
        );
    }
}
