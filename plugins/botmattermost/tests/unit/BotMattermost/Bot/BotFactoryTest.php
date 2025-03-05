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

use Tuleap\BotMattermost\Exception\EmptyUpdateException;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class BotFactoryTest extends TestCase
{
    private BotFactory $bot_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BotDao
     */
    private $bot_dao;

    protected function setUp(): void
    {
        $this->bot_dao     = $this->createMock(BotDao::class);
        $this->bot_factory = new BotFactory($this->bot_dao);
    }

    public function testItThrowsAnExceptionIfSystemBotAlreadyExists(): void
    {
        $this->bot_dao
            ->expects(self::once())
            ->method('isASystemBotWithNameAndWebhookUrlAlreadyExisting')
            ->willReturn(true);

        $this->expectException(BotAlreadyExistException::class);
        $this->bot_factory->save('testbot', 'https://test.example.com', '', null);
    }

    public function testItThrowsAnExceptionIfAProjectBotAlreadyExists(): void
    {
        $this->bot_dao
            ->expects(self::once())
            ->method('isAProjectBotWithNameWebhookUrlAndProjectIdAlreadyExisting')
            ->willReturn(true);

        $this->expectException(BotAlreadyExistException::class);
        $this->bot_factory->save('testbot', 'https://test.example.com', '', 101);
    }

    public function testItThrowsAnExceptionIfUpdateEndsUpCreatingAnAlreadyExistingBot(): void
    {
        $bot_to_be_updated = [
            'id' => 10,
            'name' => 'testbot',
            'webhook_url' => 'https://test.example.com',
            'avatar_url' => '',
            'project_id' => 101,
        ];

        $this->bot_dao
            ->expects(self::once())
            ->method('isThereAnotherProjectBotWithNameWebhookUrlAndProjectId')
            ->willReturn(true);

        $this->bot_dao
            ->expects(self::once())
            ->method('searchBotById')
            ->with(10)
            ->willReturn($bot_to_be_updated);

        $this->expectException(BotAlreadyExistException::class);
        $this->bot_factory->update('testbot', 'https://test.example.com', 'https://added.avatar.example.com', 10);
    }

    public function testItDoesNotThrowAnExceptionIfUpdateChangesTheAvatar(): void
    {
        $bot_to_be_updated = [
            'id' => 10,
            'name' => 'testbot',
            'webhook_url' => 'https://test.example.com',
            'avatar_url' => '',
            'project_id' => 101,
        ];

        $this->bot_dao
            ->expects(self::once())
            ->method('isThereAnotherProjectBotWithNameWebhookUrlAndProjectId')
            ->willReturn(false);

        $this->bot_dao
            ->expects(self::once())
            ->method('searchBotById')
            ->with(10)
            ->willReturn($bot_to_be_updated);

        $this->bot_dao
            ->expects(self::once())
            ->method('updateBot')
            ->willReturn(true);

        $this->bot_factory->update('testbot', 'https://test.example.com', 'https://added.avatar.example.com', 10);
    }

    public function testItThrowsAnExceptionIfTheUpdateIsNotChangingAnything(): void
    {
        $bot_to_be_updated = [
            'id' => 10,
            'name' => 'testbot',
            'webhook_url' => 'https://test.example.com',
            'avatar_url' => 'https://already.added.avatar.example.com',
            'project_id' => 101,
        ];

        $this->bot_dao
            ->expects(self::once())
            ->method('searchBotById')
            ->with(10)
            ->willReturn($bot_to_be_updated);

        $this->expectException(EmptyUpdateException::class);
        $this->bot_factory->update('testbot', 'https://test.example.com', 'https://already.added.avatar.example.com', 10);
    }
}
