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
 *
 */

import store from "./abstract_baseline_content";
import { create, createList } from "../../support/factories";
import * as rest_querier from "../../api/rest-querier";

describe("Compared baseline store:", () => {
    let state;
    beforeEach(() => (state = { ...store.state }));

    describe("actions", () => {
        let context;
        let getBaselineArtifacts;
        let getBaselineArtifactsByIds;

        beforeEach(() => {
            context = {
                state: { ...state, baseline_id: 1 },
                commit: jest.fn(),
                dispatch: jest.fn(),
            };
            context.dispatch.mockReturnValue(Promise.resolve());

            getBaselineArtifacts = jest.spyOn(rest_querier, "getBaselineArtifacts");
            getBaselineArtifactsByIds = jest.spyOn(rest_querier, "getBaselineArtifactsByIds");
        });

        describe("#loadAllArtifacts", () => {
            const artifacts = createList("baseline_artifact", 2);
            beforeEach(() => {
                getBaselineArtifacts.mockImplementation((id) => {
                    if (id === 1) {
                        return Promise.resolve(artifacts);
                    }
                    throw new Error("Not expected ID: " + id);
                });
                return store.actions.loadAllArtifacts(context);
            });

            it("commits 'updateFirstLevelArtifacts' with baseline artifacts", () => {
                expect(context.commit).toHaveBeenCalledWith("updateFirstLevelArtifacts", artifacts);
            });
            it("dispatches 'addArtifacts' with baseline artifacts", () => {
                expect(context.dispatch).toHaveBeenCalledWith("addArtifacts", [artifacts, []]);
            });
        });

        describe("#addArtifacts", () => {
            describe("when some linked artifacts", () => {
                const artifacts = [
                    create("baseline_artifact", { linked_artifact_ids: [1] }),
                    create("baseline_artifact", { linked_artifact_ids: [2, 3] }),
                ];

                const linked_artifacts = createList("baseline_artifact", 3, {
                    linked_artifact_ids: [13],
                });

                beforeEach(() => {
                    getBaselineArtifactsByIds.mockImplementation((baseline_id, artifacts_id) => {
                        if (
                            baseline_id === 1 &&
                            JSON.stringify([1, 2, 3]) === JSON.stringify(artifacts_id)
                        ) {
                            return Promise.resolve(linked_artifacts);
                        }
                        throw new Error("Not expected args: " + baseline_id + " " + artifacts_id);
                    });

                    context.state.baseline_id = 1;
                    return store.actions.addArtifacts(context, [artifacts, []]);
                });

                it("commit 'incrementLoadedDepthsCount'", () => {
                    expect(context.commit).toHaveBeenCalledWith("incrementLoadedDepthsCount");
                });

                it("commit 'addArtifacts' with artifacts", () => {
                    expect(context.commit).toHaveBeenCalledWith("addArtifacts", artifacts);
                });

                it("dispatch 'addArtifacts' with linked artifacts", () => {
                    expect(context.dispatch).toHaveBeenCalledWith("addArtifacts", [
                        linked_artifacts,
                        [3, 4, 1, 2],
                    ]);
                });
            });

            describe("when some linked artifacts, then it filter already searched artifact", () => {
                const artifacts = [create("baseline_artifact", { linked_artifact_ids: [9] })];

                const linked_artifact = [
                    create("baseline_artifact", {
                        id: 9,
                        linked_artifact_ids: [8],
                    }),
                ];

                const filtered_linked_artifact = [
                    create("baseline_artifact", {
                        id: 9,
                        linked_artifact_ids: [],
                    }),
                ];

                beforeEach(() => {
                    getBaselineArtifactsByIds.mockImplementation((baseline_id, artifacts_id) => {
                        if (
                            baseline_id === 1 &&
                            JSON.stringify([9]) === JSON.stringify(artifacts_id)
                        ) {
                            return Promise.resolve(linked_artifact);
                        }
                        throw new Error("Not expected args: " + baseline_id + " " + artifacts_id);
                    });

                    context.state.baseline_id = 1;
                    return store.actions.addArtifacts(context, [artifacts, [8, 9, 10]]);
                });

                it("commit 'incrementLoadedDepthsCount'", () => {
                    expect(context.commit).toHaveBeenCalledWith("incrementLoadedDepthsCount");
                });

                it("commit 'addArtifacts' with artifacts", () => {
                    expect(context.commit).toHaveBeenCalledWith("addArtifacts", artifacts);
                });

                it("dispatch 'addArtifacts' with linked artifacts", () => {
                    expect(context.dispatch).toHaveBeenCalledWith("addArtifacts", [
                        filtered_linked_artifact,
                        [8, 9, 10],
                    ]);
                });
            });

            describe("when no linked artifacts", () => {
                beforeEach(() => {
                    const artifacts = [
                        create("baseline_artifact", { linked_artifact_ids: [] }),
                        create("baseline_artifact", { linked_artifact_ids: [] }),
                    ];
                    return store.actions.addArtifacts(context, [artifacts, []]);
                });
                it("does not dispatch 'addArtifacts'", () => {
                    expect(context.dispatch).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("mutations", () => {
        describe("after comparison is reset", () => {
            beforeEach(() => store.mutations.reset(state, { baseline_id: 1 }));

            describe("#addArtifacts", () => {
                const artifact1 = create("baseline_artifact", { id: 1 });
                const artifact2 = create("baseline_artifact", { id: 2 });

                beforeEach(() => store.mutations.addArtifacts(state, [artifact1, artifact2]));

                it("adds given artifacts", () => {
                    expect(state.artifacts_by_id[1]).toEqual(artifact1);
                    expect(state.artifacts_by_id[2]).toEqual(artifact2);
                });
            });
        });
    });

    describe("getters", () => {
        describe("#findArtifactsByIds", () => {
            const artifact1 = create("baseline_artifact");
            const artifact2 = create("baseline_artifact");
            beforeEach(
                () =>
                    (state.artifacts_by_id = {
                        1: artifact1,
                        2: artifact2,
                    }),
            );
            it("returns all base artifacts with given ids", () => {
                expect(store.getters.findArtifactsByIds(state)([1, 2])).toEqual([
                    artifact1,
                    artifact2,
                ]);
            });
        });
        describe("#is_depth_limit_reached", () => {
            describe("when no artifacts on depth limit", () => {
                beforeEach(() => (state.artifacts_where_depth_limit_reached = null));
                it("returns false", () => {
                    expect(store.getters.is_depth_limit_reached(state)).toBeFalsy();
                });
            });
            describe("when some artifacts on depth limit", () => {
                beforeEach(
                    () =>
                        (state.artifacts_where_depth_limit_reached = createList(
                            "baseline_artifact",
                            2,
                        )),
                );
                it("returns true", () => {
                    expect(store.getters.is_depth_limit_reached(state)).toBeTruthy();
                });
            });
        });
        describe("#isLimitReachedOnArtifact", () => {
            const artifact = create("baseline_artifact");
            const getters = {};

            describe("when depth limit not reached", () => {
                beforeEach(() => (getters.is_depth_limit_reached = false));
                it("returns false", () => {
                    expect(
                        store.getters.isLimitReachedOnArtifact(state, getters)(artifact),
                    ).toBeFalsy();
                });
            });

            describe("when depth limit reached", () => {
                beforeEach(() => (getters.is_depth_limit_reached = true));

                describe("on given artifact", () => {
                    beforeEach(() => (state.artifacts_where_depth_limit_reached = [artifact]));
                    it("returns true", () => {
                        expect(
                            store.getters.isLimitReachedOnArtifact(state, getters)(artifact),
                        ).toBeTruthy();
                    });
                });
                describe("not reached on given artifact", () => {
                    beforeEach(
                        () =>
                            (state.artifacts_where_depth_limit_reached = createList(
                                "baseline_artifact",
                                2,
                            )),
                    );
                    it("returns false", () => {
                        expect(
                            store.getters.isLimitReachedOnArtifact(state, getters)(artifact),
                        ).toBeFalsy();
                    });
                });
            });
        });
        describe("#all_trackers", () => {
            beforeEach(
                () =>
                    (state.artifacts_by_id = {
                        1: create("baseline_artifact", { tracker_id: 1, tracker_name: "Epic" }),
                        2: create("baseline_artifact", { tracker_id: 2, tracker_name: "Story" }),
                        3: create("baseline_artifact", { tracker_id: 1, tracker_name: "Epic" }),
                    }),
            );

            it("returns all distinct trackers", () => {
                expect(store.getters.all_trackers(state)).toEqual([
                    { id: 1, name: "Epic" },
                    { id: 2, name: "Story" },
                ]);
            });
        });
    });
});
