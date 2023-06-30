<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

final class ColumnIdentifier
{
    public const BACKLOG_COLUMN = 'backlog';
    public const ARCHIVE_COLUMN = 'archive';

    public function __construct(private readonly string|int $column_id)
    {
    }

    public function isBacklog(): bool
    {
        return $this->column_id === self::BACKLOG_COLUMN;
    }

    public function isArchive(): bool
    {
        return $this->column_id === self::ARCHIVE_COLUMN;
    }

    public function getColumnId(): string|int
    {
        return $this->column_id;
    }
}
