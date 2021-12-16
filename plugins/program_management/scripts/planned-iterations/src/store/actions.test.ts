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

import * as actions from "./actions";
import * as retriever from "../helpers/iteration-content-retriever";

import type { UserStory, Iteration, State } from "../type";
import type { ActionContext } from "vuex";

jest.mock("../helpers/iteration-content-retriever");

describe("actions", () => {
    let context: ActionContext<State, State>;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {} as State,
            getters: {},
        } as unknown as ActionContext<State, State>;
    });

    describe("fetchIterationContent", () => {
        it("should fetch iteration's content, store it and return it", async () => {
            const user_stories = [{ id: 101 } as UserStory, { id: 102 } as UserStory];

            jest.spyOn(retriever, "retrieveIterationContent").mockResolvedValue(user_stories);

            const content = await actions.fetchIterationContent(context, { id: 1280 } as Iteration);

            expect(context.commit).toHaveBeenCalledWith("storeIterationContent", {
                iteration_id: 1280,
                user_stories,
            });
            expect(content).toEqual(user_stories);
        });
    });
});
