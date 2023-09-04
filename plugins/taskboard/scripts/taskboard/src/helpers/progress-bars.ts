/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { RemainingEffort } from "../type";

export function getWidthPercentage(
    initial_effort: number | null,
    remaining_effort: RemainingEffort | null,
): number {
    if (
        initial_effort === null ||
        remaining_effort === null ||
        remaining_effort.value === null ||
        initial_effort <= 0
    ) {
        return 0;
    }

    if (remaining_effort.value <= 0) {
        return 100;
    }

    const progress = initial_effort - remaining_effort.value;
    const clamped_progress = clamp(progress, 0, initial_effort);
    return (clamped_progress / initial_effort) * 100;
}

function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(min, value), max);
}
