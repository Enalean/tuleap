<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Stubs\WASM;

use Tuleap\Tracker\Tracker;
use Tuleap\TrackerFunctions\WASM\WASMFunctionPathHelper;

final class WASMFunctionPathHelperStub implements WASMFunctionPathHelper
{
    /**
     * @psalm-param non-empty-string $path
     */
    private function __construct(
        private readonly string $path,
    ) {
    }

    /**
     * @psalm-param non-empty-string $path
     */
    public static function withPath(string $path): self
    {
        return new self($path);
    }

    /**
     * @psalm-return non-empty-string
     */
    #[\Override]
    public function getPathForTracker(Tracker $tracker): string
    {
        return $this->path;
    }
}
