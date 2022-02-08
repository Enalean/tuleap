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

namespace Tuleap\Project\Icons;

use JsonException;

final class EmojiCodepointConverter
{
    /**
     * @throws JsonException
     */
    public static function convertEmojiToStoreFormat(?string $emoji): string
    {
        if (! isset($emoji) || $emoji === '') {
            return '';
        }

        return json_encode($emoji, JSON_THROW_ON_ERROR);
    }

    public static function convertStoredEmojiFormatToEmojiFormat(?string $stored_emoji): string
    {
        if ($stored_emoji === null || $stored_emoji === '') {
            return '';
        }

        try {
            return json_decode($stored_emoji, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return "";
        }
    }
}
