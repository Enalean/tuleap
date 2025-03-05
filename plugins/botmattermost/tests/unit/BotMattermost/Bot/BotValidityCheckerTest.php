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

use Tuleap\BotMattermost\Exception\BotCannotBeUsedInProjectException;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BotValidityCheckerTest extends TestCase
{
    public function testItDoesNotThrowExceptionIfBotIsASystemBot(): void
    {
        $bot = new Bot(1, 'bot', '', '', null);

        (new BotValidityChecker())->checkBotCanBeUsedInProject($bot, 101);

        $this->expectNotToPerformAssertions();
    }

    public function testItDoesNotThrowExceptionIfBotIsAProjectBotInTheSameProject(): void
    {
        $bot = new Bot(1, 'bot', '', '', 101);

        (new BotValidityChecker())->checkBotCanBeUsedInProject($bot, 101);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsAnExceptionIfBotIsAProjectBotInAnotherProject(): void
    {
        $bot = new Bot(1, 'bot', '', '', 102);

        $this->expectException(BotCannotBeUsedInProjectException::class);

        (new BotValidityChecker())->checkBotCanBeUsedInProject($bot, 101);
    }
}
