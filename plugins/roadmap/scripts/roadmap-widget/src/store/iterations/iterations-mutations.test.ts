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

import type { IterationsState } from "./type";
import type { Iteration } from "../../type";
import * as mutations from "./iterations-mutations";

describe("iterations-mutations", () => {
    it("Set level 1 iterations", () => {
        const state: IterationsState = {
            lvl1_iterations: [],
            lvl2_iterations: [],
        };

        mutations.setLvl1Iterations(state, [{ id: 1 } as Iteration]);

        expect(state.lvl1_iterations).toHaveLength(1);
        expect(state.lvl2_iterations).toHaveLength(0);
    });

    it("Set level 2 iterations", () => {
        const state: IterationsState = {
            lvl1_iterations: [],
            lvl2_iterations: [],
        };

        mutations.setLvl2Iterations(state, [{ id: 1 } as Iteration]);

        expect(state.lvl1_iterations).toHaveLength(0);
        expect(state.lvl2_iterations).toHaveLength(1);
    });
});
