/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import store from "./current_baseline";
import { create, createList } from "../support/factories";

describe("Current baseline store:", () => {
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
            beforeEach(() => store.actions.load(context, 1));

            it("reset baseline content with given baseline id", () => {
                expect(context.commit).toHaveBeenCalledWith("reset", { baseline_id: 1 });
            });
            it("reset semantics", () => {
                expect(context.commit).toHaveBeenCalledWith("semantics/reset", null, {
                    root: true,
                });
            });
            it("loads baseline with author", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadBaselineWithAuthor",
                    { baseline_id: 1 },
                    { root: true },
                );
            });
            it("loads baseline content", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadAllArtifacts");
            });
        });
    });

    describe("getters", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

        describe("#filterArtifacts", () => {
            describe("when no hidden trackers", () => {
                beforeEach(() => (state.hidden_tracker_ids = []));

                it("returns all given artifacts", () => {
                    const artifacts = createList("baseline_artifact");
                    expect(store.getters.filterArtifacts(state)(artifacts)).toEqual(artifacts);
                });
            });

            describe("when some trackers hidden", () => {
                let artifact_on_hidden_tracker = create("baseline_artifact", { tracker_id: 1 });
                let artifact_on_other_tracker = create("baseline_artifact", { tracker_id: 3 });

                beforeEach(() => (state.hidden_tracker_ids = [1, 2]));

                it("filters artifacts on hidden trackers", () => {
                    expect(
                        store.getters.filterArtifacts(state)([artifact_on_hidden_tracker]),
                    ).toEqual([]);
                });
                it("does not filter artifacts on not hidden trackers", () => {
                    expect(
                        store.getters.filterArtifacts(state)([artifact_on_other_tracker]),
                    ).toEqual([artifact_on_other_tracker]);
                });
            });
        });
    });
});
