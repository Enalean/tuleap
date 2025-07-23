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

namespace Tuleap\BotMattermost\Bot;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\BotMattermost\BotMattermostDeleted;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BotDeletorTest extends TestCase
{
    private BotDeletor $deletor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BotFactory
     */
    private $bot_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventDispatcherInterface
     */
    private $event_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->bot_factory   = $this->createMock(BotFactory::class);
        $this->event_manager = $this->createMock(EventDispatcherInterface::class);

        $this->deletor = new BotDeletor(
            $this->bot_factory,
            $this->event_manager,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItDeletesABot(): void
    {
        $bot = new Bot(1, 'bot', '', '', 101);

        $this->bot_factory
            ->expects($this->once())
            ->method('deleteBotById')
            ->with(1);

        $this->event_manager
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(BotMattermostDeleted::class));

        $this->deletor->deleteBot($bot);
    }

    public function testItStopsDeletionIfAnErrorOccured(): void
    {
        $bot = new Bot(1, 'bot', '', '', 101);

        $this->bot_factory
            ->expects($this->once())
            ->method('deleteBotById')
            ->with(1)
            ->willThrowException(
                new CannotDeleteBotException()
            );

        $this->event_manager
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(CannotDeleteBotException::class);

        $this->deletor->deleteBot($bot);
    }
}
