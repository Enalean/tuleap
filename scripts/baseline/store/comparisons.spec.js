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

import store from "./comparisons";
import { create, createList } from "../support/factories";
import { restore, rewire$getComparisons } from "../api/rest-querier";

describe("Comparisons store:", () => {
    describe("actions", () => {
        let context;

        beforeEach(() => {
            context = {
                commit: jasmine.createSpy("commit"),
                dispatch: jasmine.createSpy("dispatch")
            };
            context.dispatch.and.returnValue(Promise.resolve());
        });

        afterEach(restore);

        describe("#load", () => {
            const comparison1 = create("comparison", {
                base_baseline_id: 1,
                compared_to_baseline_id: 2,
                author_id: 4
            });
            const comparison2 = create("comparison", {
                base_baseline_id: 1,
                compared_to_baseline_id: 3,
                author_id: 5
            });

            let getComparisons;

            beforeEach(() => {
                getComparisons = jasmine.createSpy("getComparisons");
                rewire$getComparisons(getComparisons);
                getComparisons.and.returnValue(Promise.resolve([comparison1, comparison2]));

                return store.actions.load(context, { project_id: 102 });
            });

            it("dispatches 'loadUsers' for authors of comparisons returned by getComparisons()", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadUsers",
                    { user_ids: [4, 5] },
                    { root: true }
                );
            });
            it("dispatches 'loadBaselines' for all base and compared to baselines", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadBaselines",
                    { baseline_ids: [1, 2, 1, 3] },
                    { root: true }
                );
            });
            it("updated comparisons", () => {
                expect(context.commit).toHaveBeenCalledWith("updateComparisons", [
                    comparison1,
                    comparison2
                ]);
            });
        });
    });

    describe("getters", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

        describe("#are_some_available", () => {
            describe("when comparisons not loaded yet", () => {
                beforeEach(() => (state.comparisons = null));
                it("returns false", () => {
                    expect(store.getters.are_some_available(state)).toBeFalsy();
                });
            });
            describe("when comparisons are loaded", () => {
                describe("when no comparisons", () => {
                    beforeEach(() => (state.comparisons = []));
                    it("returns false", () => {
                        expect(store.getters.are_some_available(state)).toBeFalsy();
                    });
                });
                describe("when some comparisons", () => {
                    beforeEach(() => (state.comparisons = createList("comparison", 2)));
                    it("returns false", () => {
                        expect(store.getters.are_some_available(state)).toBeTruthy();
                    });
                });
            });
        });
    });
});
