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

use Tuleap\BotMattermost\Administration\Request\ParameterValidator;
use Tuleap\BotMattermost\Exception\ProvidedBotParameterIsNotValidException;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BotCreatorTest extends TestCase
{
    private BotCreator $bot_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BotFactory
     */
    private $bot_factory;

    protected function setUp(): void
    {
        $this->bot_factory = $this->createMock(BotFactory::class);

        $this->bot_creator = new BotCreator(
            $this->bot_factory,
            new ParameterValidator()
        );
    }

    public function testItCreatesBot(): void
    {
        $this->bot_factory
            ->expects($this->once())
            ->method('save');

        $this->bot_creator->createSystemBot(
            'Bot name',
            'https://example.com',
            'https://example.com',
        );
    }

    public function testItDoesNotCreateBotIfMandatoryBotNameIsMissing(): void
    {
        $this->bot_factory
            ->expects(self::never())
            ->method('save');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_creator->createSystemBot(
            '',
            'https://example.com',
            'https://example.com',
        );
    }

    public function testItDoesNotCreateBotIfMandatoryWebhookURLIsMissing(): void
    {
        $this->bot_factory
            ->expects(self::never())
            ->method('save');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_creator->createSystemBot(
            'Name',
            '',
            'https://example.com',
        );
    }

    public function testItDoesNotCreateBotIfMandatoryWebhookURLIsNotAnHTTPURL(): void
    {
        $this->bot_factory
            ->expects(self::never())
            ->method('save');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_creator->createSystemBot(
            'Name',
            'http://example.com',
            'https://example.com',
        );
    }

    public function testItCreatesBotIfOptionalAvatarURLIsMissing(): void
    {
        $this->bot_factory
            ->expects($this->once())
            ->method('save');

        $this->bot_creator->createSystemBot(
            'Name',
            'https://example.com',
            '',
        );
    }

    public function testItDoesNotCreateBotIfOptionalAvatarURLIsNotHTTPS(): void
    {
        $this->bot_factory
            ->expects(self::never())
            ->method('save');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_creator->createSystemBot(
            'Name',
            'https://example.com',
            'http://example.com',
        );
    }
}
