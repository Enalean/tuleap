/*
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

import * as getters from "./getters";
import type { UserStory, Iteration, State } from "../type";

describe("getters", () => {
    let state: State;

    beforeEach(() => {
        state = { iterations_content: new Map<number, UserStory[]>() } as State;
    });

    describe("hasIterationContentInStore", () => {
        it("should return true when the content of an iteration is already stored", () => {
            state.iterations_content.set(1280, []);

            expect(getters.hasIterationContentInStore(state)({ id: 1280 } as Iteration)).toBe(true);
        });

        it("should return false when the content of an iteration is not stored yet", () => {
            expect(getters.hasIterationContentInStore(state)({ id: 1280 } as Iteration)).toBe(
                false,
            );
        });
    });

    describe("getIterationContentFromStore", () => {
        it("should return an empty array when there is no stored content for the given iteration", () => {
            expect(getters.getIterationContentFromStore(state)({ id: 1280 } as Iteration)).toEqual(
                [],
            );
        });

        it("should return the stored content for the given iteration", () => {
            const us_1281 = { id: 1281 } as UserStory;
            state.iterations_content.set(1280, [us_1281]);

            expect(getters.getIterationContentFromStore(state)({ id: 1280 } as Iteration)).toEqual([
                us_1281,
            ]);
        });
    });
});
