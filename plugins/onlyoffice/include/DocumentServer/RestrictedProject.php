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

namespace Tuleap\OnlyOffice\DocumentServer;

use Tuleap\Project\Icons\EmojiCodepointConverter;

/**
 * @psalm-immutable
 */
final class RestrictedProject
{
    public function __construct(public int $id, public string $name, public string $label)
    {
    }

    /**
     * @param array{project_id: int, name: string, label: string, icon_codepoint: string} $row
     */
    public static function fromRow(array $row): self
    {
        $project_icon = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($row['icon_codepoint']);

        return new self(
            $row['project_id'],
            $row['name'],
            $project_icon ? $project_icon . ' ' . $row['label'] : $row['label']
        );
    }
}
