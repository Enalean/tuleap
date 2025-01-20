<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\WebAssembly;

/**
 * @psalm-immutable
 */
final readonly class WASMRuntimeLimits
{
    private const MAX_EXEC_TIME_IN_MS      = 10;
    private const MAX_MEMORY_SIZE_IN_BYTES = 4194304; /* 4 Mo */

    /**
     * @psalm-param positive-int $max_exec_time_in_ms
     * @psalm-param positive-int $max_memory_size_in_bytes
     */
    public function __construct(
        public int $max_exec_time_in_ms,
        public int $max_memory_size_in_bytes,
    ) {
    }

    public static function getDefaultLimits(): self
    {
        return new self(
            self::MAX_EXEC_TIME_IN_MS,
            self::MAX_MEMORY_SIZE_IN_BYTES
        );
    }
}
