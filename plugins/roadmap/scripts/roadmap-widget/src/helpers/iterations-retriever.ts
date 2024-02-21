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

import { recursiveGet } from "@tuleap/tlp-fetch";
import type { Iteration, IterationLevel } from "../type";

export function retrieveIterations(
    roadmap_id: number,
    level: IterationLevel,
): Promise<Iteration[]> {
    return recursiveGet<Array<Iteration>, Iteration>(`/api/roadmaps/${roadmap_id}/iterations`, {
        params: { level },
        getCollectionCallback: (iterations: Iteration[]): Iteration[] => {
            return iterations.map((iteration: Iteration): Iteration => {
                return {
                    ...iteration,
                    start: structuredClone(iteration.start),
                    end: structuredClone(iteration.end),
                };
            });
        },
    });
}
