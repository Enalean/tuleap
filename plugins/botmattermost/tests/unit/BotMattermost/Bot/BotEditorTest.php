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
final class BotEditorTest extends TestCase
{
    private BotEditor $bot_editor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BotFactory
     */
    private $bot_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->bot_factory = $this->createMock(BotFactory::class);

        $this->bot_editor = new BotEditor(
            $this->bot_factory,
            new ParameterValidator()
        );
    }

    public function testItEditsBot(): void
    {
        $this->bot_factory
            ->expects($this->once())
            ->method('update');

        $this->bot_editor->editBotById(
            1,
            'Bot name',
            'https://example.com',
            'https://example.com',
        );
    }

    public function testItDoesNotEditBotIfMandatoryBotNameIsMissing(): void
    {
        $this->bot_factory
            ->expects($this->never())
            ->method('update');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_editor->editBotById(
            1,
            '',
            'https://example.com',
            'https://example.com',
        );
    }

    public function testItDoesNotEditBotIfMandatoryWebhookURLIsMissing(): void
    {
        $this->bot_factory
            ->expects($this->never())
            ->method('update');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_editor->editBotById(
            1,
            'Name',
            '',
            'https://example.com',
        );
    }

    public function testItDoesNotEditBotIfMandatoryWebhookURLIsNotAnHTTPURL(): void
    {
        $this->bot_factory
            ->expects($this->never())
            ->method('update');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_editor->editBotById(
            1,
            'Name',
            'http://example.com',
            'https://example.com',
        );
    }

    public function testItEditsBotIfOptionalAvatarURLIsMissing(): void
    {
        $this->bot_factory
            ->expects($this->once())
            ->method('update');

        $this->bot_editor->editBotById(
            1,
            'Name',
            'https://example.com',
            '',
        );
    }

    public function testItDoesNotEditBotIfOptionalAvatarURLIsNotHTTPS(): void
    {
        $this->bot_factory
            ->expects($this->never())
            ->method('update');

        $this->expectException(ProvidedBotParameterIsNotValidException::class);

        $this->bot_editor->editBotById(
            1,
            'Name',
            'https://example.com',
            'http://example.com',
        );
    }
}
