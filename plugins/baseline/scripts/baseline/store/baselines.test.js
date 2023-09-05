/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import { create, createList } from "../support/factories";
import * as rest_querier from "../api/rest-querier";

describe("Baselines store:", () => {
    describe("actions", () => {
        let context;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
                dispatch: jest.fn(),
            };
            context.dispatch.mockReturnValue(Promise.resolve());
        });

        describe("#load", () => {
            const baseline1 = create("baseline", { artifact_id: 1, author_id: 4 });
            const baseline2 = create("baseline", { artifact_id: 2, author_id: 5 });

            beforeEach(() => {
                jest.spyOn(rest_querier, "getBaselines").mockReturnValue(
                    Promise.resolve([baseline1, baseline2]),
                );

                return store.actions.load(context, { project_id: 102 });
            });

            it("dispatches 'loadUsers' for users of baselines returned by getBaselines()", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadUsers",
                    { user_ids: [4, 5] },
                    { root: true },
                );
            });
            it("dispatches 'loadArtifacts' for artifacts of baselines", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadArtifacts",
                    { artifact_ids: [1, 2] },
                    { root: true },
                );
            });
            it("updated baselines", () => {
                expect(context.commit).toHaveBeenCalledWith("updateBaselines", [
                    baseline1,
                    baseline2,
                ]);
            });
        });
    });
    describe("mutations", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

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

    describe("getters", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

        describe("#are_baselines_available", () => {
            describe("when baselines not loaded yet", () => {
                beforeEach(() => (state.baselines = null));
                it("returns false", () => {
                    expect(store.getters.are_baselines_available(state)).toBeFalsy();
                });
            });
            describe("when baselines are loaded", () => {
                describe("when no baselines", () => {
                    beforeEach(() => (state.baselines = []));
                    it("returns false", () => {
                        expect(store.getters.are_baselines_available(state)).toBeFalsy();
                    });
                });
                describe("when some baselines", () => {
                    beforeEach(() => (state.baselines = createList("baseline", 2)));
                    it("returns false", () => {
                        expect(store.getters.are_baselines_available(state)).toBeTruthy();
                    });
                });
            });
        });
    });
});
