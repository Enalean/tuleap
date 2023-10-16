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

import store from "./index";
import * as comparison from "../../support/comparison";

describe("Current comparison store:", () => {
    describe("actions", () => {
        let context;
        let getters = {
            "base/findArtifactsByIds": () => {},
            "compared_to/findArtifactsByIds": () => {},
        };

        beforeEach(() => {
            context = {
                state: { ...store.state },
                commit: jest.fn(),
                dispatch: jest.fn(),
                getters,
            };
            context.dispatch.mockReturnValue(Promise.resolve());
        });

        describe("#load", () => {
            beforeEach(() =>
                store.actions.load(context, { base_baseline_id: 1, compared_to_baseline_id: 2 }),
            );

            it("commits 'startNewComparison'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("startNewComparison", {
                    base_baseline_id: 1,
                    compared_to_baseline_id: 2,
                });
            });
            it("dispatches 'loadBaseline' with baseline ids", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadBaseline",
                    { baseline_id: 1 },
                    { root: true },
                );
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadBaseline",
                    { baseline_id: 2 },
                    { root: true },
                );
            });
            it("dispatches 'loadAllArtifacts' on compared baselines", () => {
                expect(context.dispatch).toHaveBeenCalledWith("base/loadAllArtifacts");
                expect(context.dispatch).toHaveBeenCalledWith("compared_to/loadAllArtifacts");
            });
        });

        describe("#startNewComparison", () => {
            beforeEach(() =>
                store.actions.startNewComparison(context, {
                    base_baseline_id: 1,
                    compared_to_baseline_id: 2,
                }),
            );

            it("commit 'rest' on compared baseline", () => {
                expect(context.commit).toHaveBeenCalledWith("base/reset", {
                    baseline_id: 1,
                });
                expect(context.commit).toHaveBeenCalledWith("compared_to/reset", {
                    baseline_id: 2,
                });
            });
        });

        describe("#compareArtifacts", () => {
            const base_artifact = {
                id: 101,
                title: "Sprint-1",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [1],
            };
            const compared_to_artifact = {
                id: 102,
                title: "Sprint-2",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [1, 2],
            };

            const linked_base_artifacts = [
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
            ];
            const linked_compared_to_artifacts = [
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
                {
                    id: 3,
                    title: "Sprint-3",
                    status: "Planned",
                    tracker_id: 1,
                    initial_effort: null,
                    tracker_name: "Sprint",
                    description:
                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                    linked_artifact_ids: [],
                },
            ];

            const artifacts_comparison = {
                identical_or_modified: [
                    {
                        base: base_artifact,
                        compared_to: compared_to_artifact,
                    },
                ],
            };

            beforeEach(() => {
                jest.spyOn(getters, "base/findArtifactsByIds").mockImplementation((ids) => {
                    if (JSON.stringify([1]) === JSON.stringify(ids)) {
                        return linked_base_artifacts;
                    }
                    throw new Error("Not expected IDs");
                });

                jest.spyOn(getters, "compared_to/findArtifactsByIds").mockImplementation((ids) => {
                    if (JSON.stringify([1, 2]) === JSON.stringify(ids)) {
                        return linked_compared_to_artifacts;
                    }
                    throw new Error("Not expected IDs");
                });

                jest.spyOn(comparison, "compareArtifacts").mockReturnValue(artifacts_comparison);

                return store.actions.compareArtifacts(context, {
                    base_artifacts: [base_artifact],
                    compared_to_artifacts: [compared_to_artifact],
                });
            });

            it("commits 'incrementStatistics' with comparison of given artifacts", () => {
                expect(context.commit).toHaveBeenCalledWith(
                    "incrementStatistics",
                    artifacts_comparison,
                );
            });
            it("compares linked artifacts", () => {
                expect(context.dispatch).toHaveBeenCalledWith("compareArtifacts", {
                    base_artifacts: linked_base_artifacts,
                    compared_to_artifacts: linked_compared_to_artifacts,
                });
            });
        });
    });

    describe("mutations", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

        describe("after reset", () => {
            beforeEach(() => store.mutations.reset(state));

            describe("#incrementStatistics", () => {
                beforeEach(() => {
                    const artifacts_comparison = {
                        identical_or_modified: [],
                        added: [
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
                        ],
                        removed: [
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
                            {
                                id: 103,
                                title: "Sprint-3",
                                status: "Planned",
                                tracker_id: 1,
                                initial_effort: null,
                                tracker_name: "Sprint",
                                description:
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                linked_artifact_ids: [],
                            },
                        ],
                        modified: [
                            {
                                id: 104,
                                title: "Sprint-4",
                                status: "Planned",
                                tracker_id: 1,
                                initial_effort: null,
                                tracker_name: "Sprint",
                                description:
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                linked_artifact_ids: [],
                            },
                            {
                                id: 105,
                                title: "Sprint-5",
                                status: "Planned",
                                tracker_id: 1,
                                initial_effort: null,
                                tracker_name: "Sprint",
                                description:
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                linked_artifact_ids: [],
                            },
                            {
                                id: 106,
                                title: "Sprint-6",
                                status: "Planned",
                                tracker_id: 1,
                                initial_effort: null,
                                tracker_name: "Sprint",
                                description:
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                linked_artifact_ids: [],
                            },
                        ],
                    };
                    store.mutations.incrementStatistics(state, artifacts_comparison);
                });

                it("updates count statistics", () => {
                    expect(state.added_artifacts_count).toBe(1);
                    expect(state.removed_artifacts_count).toBe(2);
                    expect(state.modified_artifacts_count).toBe(3);
                });

                describe("when comparison contains identical artifacts", () => {
                    describe("with same initial effort", () => {
                        beforeEach(() => {
                            const artifacts_comparison = {
                                identical_or_modified: [
                                    {
                                        base: {
                                            id: 1,
                                            title: "Sprint-1",
                                            status: "Planned",
                                            tracker_id: 1,
                                            initial_effort: 3,
                                            tracker_name: "Sprint",
                                            description:
                                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                            linked_artifact_ids: [],
                                        },
                                        compared_to: {
                                            id: 1,
                                            title: "Sprint-1",
                                            status: "Planned",
                                            tracker_id: 1,
                                            initial_effort: 3,
                                            tracker_name: "Sprint",
                                            description:
                                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                            linked_artifact_ids: [],
                                        },
                                    },
                                ],
                                added: [],
                                removed: [],
                                modified: [],
                            };
                            store.mutations.incrementStatistics(state, artifacts_comparison);
                        });
                        it("does not modify initial effort statistics", () => {
                            expect(state.initial_effort_difference).toBe(0);
                        });
                    });

                    describe("with not same initial effort", () => {
                        beforeEach(() => {
                            const artifacts_comparison = {
                                identical_or_modified: [
                                    {
                                        base: {
                                            id: 1,
                                            title: "Sprint-1",
                                            status: "Planned",
                                            tracker_id: 1,
                                            initial_effort: 3,
                                            tracker_name: "Sprint",
                                            description:
                                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                            linked_artifact_ids: [],
                                        },
                                        compared_to: {
                                            id: 1,
                                            title: "Sprint-1",
                                            status: "Planned",
                                            tracker_id: 1,
                                            initial_effort: 5,
                                            tracker_name: "Sprint",
                                            description:
                                                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                                            linked_artifact_ids: [],
                                        },
                                    },
                                ],
                                added: [],
                                removed: [],
                                modified: [],
                            };
                            store.mutations.incrementStatistics(state, artifacts_comparison);
                        });
                        it("updates initial effort statistics", () => {
                            expect(state.initial_effort_difference).toBe(2);
                        });
                    });
                });
            });
        });
    });
});
