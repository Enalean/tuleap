/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import store from "./baselines";
import { create } from "../support/factories";

describe("Baselines store:", () => {
    let state;

    beforeEach(() => {
        state = { ...store.state };
    });

    describe("mutations", () => {
        describe("#delete", () => {
            const baseline_to_delete = create("baseline", { id: 1 });
            const another_baseline = create("baseline", { id: 2 });

            beforeEach(() => {
                state.baselines = [baseline_to_delete, another_baseline];
                store.mutations.delete(state, baseline_to_delete);
            });

            it("removes given baseline from state", () => {
                expect(state.baselines).not.toContain(baseline_to_delete);
            });
            it("does not remove other baselines from state", () => {
                expect(state.baselines).toContain(another_baseline);
            });
        });
    });
});
