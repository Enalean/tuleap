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

namespace Tuleap\Docman\Log;

/**
 * @psalm-immutable
 */
final class LogEntryPage
{
    /**
     * @psalm-param positive-int|0 $total
     * @param LogEntry[] $entries
     */
    private function __construct(
        public int $total,
        public array $entries,
    ) {
    }

    public static function noLog(): self
    {
        return new self(0, []);
    }

    /**
     * @param positive-int $total
     * @param LogEntry[] $entries
     */
    public static function page(int $total, array $entries): self
    {
        return new self($total, $entries);
    }
}
