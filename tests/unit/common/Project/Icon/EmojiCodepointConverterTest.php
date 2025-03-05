<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Icon;

use Tuleap\Project\Icons\EmojiCodepointConverter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EmojiCodepointConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsEmptyCharacterIfThereIsNothing(): void
    {
        self::assertEquals('', EmojiCodepointConverter::convertEmojiToStoreFormat(null));
    }

    public function testItReturnsEmptyCharacterIfNoCharacterIsGiven(): void
    {
        self::assertEquals('', EmojiCodepointConverter::convertEmojiToStoreFormat(''));
    }

    public function testItReturnsTheJsonEncodedEmojiWhichWillBeStored(): void
    {
        self::assertEquals('"\ud83d\ude2c"', EmojiCodepointConverter::convertEmojiToStoreFormat('😬'));
    }

    public function testItReturnsEmptyCharacterIfThereIsNoStoredEmoji(): void
    {
        self::assertEquals('', EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat(null));
    }

    public function testItReturnsTheStoredEmojiCharacter(): void
    {
        self::assertEquals('😬', EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat('"\ud83d\ude2c"'));
    }

    public function testItReturnsEmptyWhenJsonDecodeFail(): void
    {
        self::assertEquals('', EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat('"\aaa\bbb"'));
    }
}
