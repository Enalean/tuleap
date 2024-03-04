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
 */

import type { Iteration, Task } from "../type";
import type { DateTime } from "luxon";

export function getFirstDate(elements: (Task | Iteration)[], now: DateTime): DateTime {
    const first_date = elements.reduce(
        (first: DateTime | null, current: Task | Iteration): DateTime | null => {
            if (!first) {
                return current.start || current.end;
            }

            if (current.start && current.start < first) {
                return current.start;
            }

            if (!current.start && current.end && current.end < first) {
                return current.end;
            }

            return first;
        },
        null,
    );

    if (!first_date) {
        return now;
    }

    return first_date < now ? first_date : now;
}
