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
import { create, createList } from "../../support/factories";
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
            const base_artifact = create("baseline_artifact", { linked_artifact_ids: [1] });
            const compared_to_artifact = create("baseline_artifact", {
                linked_artifact_ids: [1, 2],
            });

            const linked_base_artifacts = createList("baseline_artifact", 1);
            const linked_compared_to_artifacts = createList("baseline_artifact", 2);

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
                    const artifacts_comparison = create("artifacts_comparison", "empty", {
                        added: createList("baseline_artifact", 1),
                        removed: createList("baseline_artifact", 2),
                        modified: createList("baseline_artifact", 3),
                    });
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
                            const artifacts_comparison = create("artifacts_comparison", "empty", {
                                identical_or_modified: [
                                    {
                                        base: create("baseline_artifact", { initial_effort: 3 }),
                                        compared_to: create("baseline_artifact", {
                                            initial_effort: 3,
                                        }),
                                    },
                                ],
                            });
                            store.mutations.incrementStatistics(state, artifacts_comparison);
                        });
                        it("does not modify initial effort statistics", () => {
                            expect(state.initial_effort_difference).toBe(0);
                        });
                    });

                    describe("with not same initial effort", () => {
                        beforeEach(() => {
                            const artifacts_comparison = create("artifacts_comparison", "empty", {
                                identical_or_modified: [
                                    {
                                        base: create("baseline_artifact", { initial_effort: 3 }),
                                        compared_to: create("baseline_artifact", {
                                            initial_effort: 5,
                                        }),
                                    },
                                ],
                            });
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
