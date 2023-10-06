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
            const artifacts = [
                {
                    id: 1,
                    title: "Sprint-1",
                    status: "Planned",
                    tracker_id: 1,
                    initial_effort: null,
                    tracker_name: "Sprint",
                    description:
                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                    linked_artifact_ids: [],
                },
                {
                    id: 2,
                    title: "Sprint-2",
                    status: "Planned",
                    tracker_id: 1,
                    initial_effort: null,
                    tracker_name: "Sprint",
                    description:
                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                    linked_artifact_ids: [],
                },
            ];
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
                    {
                        id: 3,
                        title: "Sprint-3",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [1],
                    },
                    {
                        id: 4,
                        title: "Sprint-4",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [2, 3],
                    },
                ];

                const linked_artifacts = [
                    {
                        id: 5,
                        title: "Sprint-5",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [13],
                    },
                    {
                        id: 6,
                        title: "Sprint-6",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [13],
                    },
                    {
                        id: 7,
                        title: "Sprint-7",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [13],
                    },
                ];

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
                const artifacts = [
                    {
                        id: 8,
                        title: "Sprint-8",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [9],
                    },
                ];

                const linked_artifact = [
                    {
                        id: 9,
                        title: "Sprint-9",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [8],
                    },
                ];

                const filtered_linked_artifact = [
                    {
                        id: 9,
                        title: "Sprint-9",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [],
                    },
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
                        {
                            id: 10,
                            title: "Sprint-10",
                            status: "Planned",
                            tracker_id: 1,
                            initial_effort: null,
                            tracker_name: "Sprint",
                            description:
                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                            linked_artifact_ids: [],
                        },
                        {
                            id: 11,
                            title: "Sprint-11",
                            status: "Planned",
                            tracker_id: 1,
                            initial_effort: null,
                            tracker_name: "Sprint",
                            description:
                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                            linked_artifact_ids: [],
                        },
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
                const artifact1 = {
                    id: 1,
                    title: "Sprint-1",
                    status: "Planned",
                    tracker_id: 1,
                    initial_effort: null,
                    tracker_name: "Sprint",
                    description:
                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                    linked_artifact_ids: [1],
                };
                const artifact2 = {
                    id: 2,
                    title: "Sprint-2",
                    status: "Planned",
                    tracker_id: 1,
                    initial_effort: null,
                    tracker_name: "Sprint",
                    description:
                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                    linked_artifact_ids: [2, 3],
                };

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
            const artifact1 = {
                id: 1,
                title: "Sprint-1",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [1],
            };
            const artifact2 = {
                id: 2,
                title: "Sprint-2",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [2, 3],
            };
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
                        (state.artifacts_where_depth_limit_reached = [
                            {
                                id: 101,
                                title: "Sprint-1",
                                status: "Planned",
                                tracker_id: 1,
                                initial_effort: null,
                                tracker_name: "Sprint",
                                description:
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                linked_artifact_ids: [],
                            },
                            {
                                id: 102,
                                title: "Sprint-2",
                                status: "Planned",
                                tracker_id: 1,
                                initial_effort: null,
                                tracker_name: "Sprint",
                                description:
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                linked_artifact_ids: [],
                            },
                        ]),
                );
                it("returns true", () => {
                    expect(store.getters.is_depth_limit_reached(state)).toBeTruthy();
                });
            });
        });
        describe("#isLimitReachedOnArtifact", () => {
            const artifact = {
                id: 1,
                title: "Sprint-1",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [1],
            };
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
                            (state.artifacts_where_depth_limit_reached = [
                                {
                                    id: 101,
                                    title: "Sprint-1",
                                    status: "Planned",
                                    tracker_id: 1,
                                    initial_effort: null,
                                    tracker_name: "Sprint",
                                    description:
                                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                    linked_artifact_ids: [],
                                },
                                {
                                    id: 102,
                                    title: "Sprint-2",
                                    status: "Planned",
                                    tracker_id: 1,
                                    initial_effort: null,
                                    tracker_name: "Sprint",
                                    description:
                                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                    linked_artifact_ids: [],
                                },
                            ]),
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
                        1: {
                            id: 1,
                            title: "Epic-1",
                            status: "Planned",
                            tracker_id: 1,
                            initial_effort: null,
                            tracker_name: "Epic",
                            description:
                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                            linked_artifact_ids: [],
                        },
                        2: {
                            id: 2,
                            title: "Story-2",
                            status: "Planned",
                            tracker_id: 2,
                            initial_effort: null,
                            tracker_name: "Story",
                            description:
                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                            linked_artifact_ids: [],
                        },
                        3: {
                            id: 3,
                            title: "Epic-3",
                            status: "Planned",
                            tracker_id: 1,
                            initial_effort: null,
                            tracker_name: "Epic",
                            description:
                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                            linked_artifact_ids: [],
                        },
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
