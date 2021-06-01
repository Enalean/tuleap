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

import * as actions from "./root-actions";
import type { ActionContext } from "vuex";
import type { RootState } from "./type";

describe("root-actions", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        context = {
            dispatch: jest.fn(),
        } as unknown as ActionContext<RootState, RootState>;
        jest.clearAllMocks();
    });

    describe("loadRoadmap", () => {
        it("loads tasks", () => {
            actions.loadRoadmap(context, 42);
            expect(context.dispatch).toHaveBeenCalledWith("tasks/loadTasks", 42);
        });
    });
});
