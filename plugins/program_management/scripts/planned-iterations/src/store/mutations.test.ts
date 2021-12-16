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

import * as mutations from "./mutations";
import type { UserStory, State } from "../type";

describe("mutations", () => {
    let state: State;

    beforeEach(() => {
        state = { iterations_content: new Map<number, UserStory[]>() } as State;
    });

    describe("storeIterationContent", () => {
        it("should store the iteration content", () => {
            const us_1281 = { id: 1281 } as UserStory;

            mutations.storeIterationContent(state, {
                iteration_id: 1280,
                user_stories: [{ id: 1281 } as UserStory],
            });

            expect(Array.from(state.iterations_content.entries())).toEqual([[1280, [us_1281]]]);
        });
    });
});
