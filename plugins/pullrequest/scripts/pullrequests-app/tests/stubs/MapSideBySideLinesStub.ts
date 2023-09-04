/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type {
    MapSideBySideLines,
    SynchronizedLineHandles,
} from "../../src/app/file-diff/file-lines/SideBySideLineMapper";
import type { FileLine } from "../../src/app/file-diff/types";

export interface StubSideBySideLineMapper {
    withSideBySideLineMap: (map: Map<FileLine, SynchronizedLineHandles>) => MapSideBySideLines;
    getNbCalls: () => number;
}

export const MapSideBySideLinesStub = (): StubSideBySideLineMapper => {
    let nb_calls = 0;

    return {
        withSideBySideLineMap: (
            map: Map<FileLine, SynchronizedLineHandles>,
        ): MapSideBySideLines => ({
            buildLineToLineHandlesMap: (): Map<FileLine, SynchronizedLineHandles> => {
                nb_calls++;

                return map;
            },
        }),
        getNbCalls: (): number => nb_calls,
    };
};
