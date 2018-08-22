/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import * as actions from "./actions.js";

import { rewire$getBranches, restore as restoreRestQuerier } from "../api/rest-querier.js";

describe("Store actions", () => {
    let getBranches;

    beforeEach(() => {
        getBranches = jasmine.createSpy("getBranches");
        rewire$getBranches(getBranches);
    });

    afterEach(() => {
        restoreRestQuerier();
    });

    describe("init", () => {
        let context;
        beforeEach(() => {
            context = {
                commit: jasmine.createSpy("commit")
            };
        });

        it("loads branches of current repository and store them as source branches", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            getBranches.withArgs(42).and.returnValue(Promise.resolve(branches));

            await actions.init(context, { repository_id: 42 });

            expect(getBranches).toHaveBeenCalledWith(42);
            expect(context.commit).toHaveBeenCalledWith("setSourceBranches", branches);
        });

        it("loads branches of current repository and store them as destination branches if there is no parent repository", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            getBranches.withArgs(42).and.returnValue(Promise.resolve(branches));

            await actions.init(context, { repository_id: 42 });

            expect(context.commit).toHaveBeenCalledWith("setDestinationBranches", branches);
        });

        it("loads branches of parent repository and add them as destination branches", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            const parent_branches = [{ name: "master" }, { name: "dev" }];
            getBranches.withArgs(42).and.returnValue(Promise.resolve(branches));
            getBranches.withArgs(66).and.returnValue(Promise.resolve(parent_branches));

            await actions.init(context, { repository_id: 42, parent_repository_id: 66 });

            expect(context.commit).toHaveBeenCalledWith(
                "setDestinationBranches",
                branches.concat(parent_branches)
            );
        });
    });
});
