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

namespace Tuleap\TrackerFunctions\WASM;

use Tuleap\Tracker\Tracker;

final class FindWASMFunctionPath implements WASMFunctionPathHelper
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $base_path;

    public function __construct()
    {
        $this->base_path = ((string) \ForgeConfig::get('sys_data_dir')) . '/tracker_functions/';
    }

    /**
     * @psalm-return non-empty-string
     */
    #[\Override]
    public function getPathForTracker(Tracker $tracker): string
    {
        return $this->base_path . $tracker->getId() . '/post-action.wasm';
    }
}
