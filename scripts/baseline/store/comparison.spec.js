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

import store from "./comparison";
import { create, createList } from "../support/factories";
import {
    restore as restoreRestQuerier,
    rewire$getBaselineArtifacts,
    rewire$getBaselineArtifactsByIds
} from "../api/rest-querier";
import { restore as restoreComparison, rewire$compareArtifacts } from "../support/comparison";

describe("Comparison store:", () => {
    describe("actions", () => {
        let context;
        let getBaselineArtifacts;
        let getBaselineArtifactsByIds;

        beforeEach(() => {
            context = {
                state: { ...store.state },
                commit: jasmine.createSpy("commit"),
                dispatch: jasmine.createSpy("dispatch")
            };
            context.dispatch.and.returnValue(Promise.resolve());

            getBaselineArtifacts = jasmine.createSpy("getBaselineArtifacts");
            rewire$getBaselineArtifacts(getBaselineArtifacts);

            getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
            rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);
        });

        afterEach(() => {
            restoreRestQuerier();
            restoreComparison();
        });

        describe("#load", () => {
            beforeEach(() =>
                store.actions.load(context, { base_baseline_id: 1, compared_to_baseline_id: 2 }));

            it("commits 'startNewComparison'", () => {
                expect(context.commit).toHaveBeenCalledWith("startNewComparison", {
                    base_baseline_id: 1,
                    compared_to_baseline_id: 2
                });
            });
            it("dispatches 'loadBaseline' with baseline ids", () => {
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadBaseline",
                    { baseline_id: 1 },
                    { root: true }
                );
                expect(context.dispatch).toHaveBeenCalledWith(
                    "loadBaseline",
                    { baseline_id: 2 },
                    { root: true }
                );
            });
            it("dispatches 'loadAllArtifacts'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadAllArtifacts");
            });
        });

        describe("#loadAllArtifacts", () => {
            const base_artifacts = createList("baseline_artifact", 2);
            const compared_to_artifacts = createList("baseline_artifact", 2);

            beforeEach(() => {
                getBaselineArtifacts.withArgs(1).and.returnValue(Promise.resolve(base_artifacts));
                getBaselineArtifacts
                    .withArgs(2)
                    .and.returnValue(Promise.resolve(compared_to_artifacts));

                context.state.base_baseline_id = 1;
                context.state.compared_to_baseline_id = 2;
                return store.actions.loadAllArtifacts(context);
            });

            it("dispatches 'loadArtifacts'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifacts", {
                    base_artifacts,
                    compared_to_artifacts
                });
            });
        });

        describe("#loadArtifacts", () => {
            let compareArtifacts;

            const base_artifact = create("baseline_artifact");
            const compared_to_artifact = create("baseline_artifact");

            const artifacts_comparison = {
                identical_or_modified: [
                    {
                        base: base_artifact,
                        compared_to: compared_to_artifact
                    }
                ]
            };

            beforeEach(() => {
                compareArtifacts = jasmine
                    .createSpy("compareArtifacts")
                    .and.returnValue(artifacts_comparison);
                rewire$compareArtifacts(compareArtifacts);

                return store.actions.loadArtifacts(context, {
                    base_artifacts: [base_artifact],
                    compared_to_artifacts: [compared_to_artifact]
                });
            });

            it("adds artifacts", () => {
                expect(context.commit).toHaveBeenCalledWith("addBaseArtifacts", [base_artifact]);
                expect(context.commit).toHaveBeenCalledWith("addComparedToArtifacts", [
                    compared_to_artifact
                ]);
            });
            it("updates statistics", () => {
                expect(context.commit).toHaveBeenCalledWith(
                    "updateStatistics",
                    artifacts_comparison
                );
            });
            it("dispatch 'loadLinkedArtifacts' for each identical or modified artifacts", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadLinkedArtifacts", {
                    base_artifact,
                    compared_to_artifact
                });
            });
        });

        describe("#loadLinkedArtifacts", () => {
            const linked_base_artifacts = createList("baseline_artifact", 2);
            const linked_compared_to_artifacts = createList("baseline_artifact", 1);

            beforeEach(() => {
                getBaselineArtifactsByIds
                    .withArgs(1, [4, 5])
                    .and.returnValue(linked_base_artifacts);
                getBaselineArtifactsByIds
                    .withArgs(2, [6])
                    .and.returnValue(linked_compared_to_artifacts);

                context.state.base_baseline_id = 1;
                context.state.compared_to_baseline_id = 2;

                const base_artifact = create("baseline_artifact", { linked_artifact_ids: [4, 5] });
                const compared_to_artifact = create("baseline_artifact", {
                    linked_artifact_ids: [6]
                });

                return store.actions.loadLinkedArtifacts(context, {
                    base_artifact,
                    compared_to_artifact
                });
            });

            it("dispatches 'loadArtifacts' with linked artifacts", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifacts", {
                    base_artifacts: linked_base_artifacts,
                    compared_to_artifacts: linked_compared_to_artifacts
                });
            });
        });
    });

    describe("mutations", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

        describe("after comparison is started", () => {
            beforeEach(() =>
                store.mutations.startNewComparison(state, {
                    base_baseline_id: 1,
                    compared_to_baseline_id: 2
                }));

            describe("#addBaseArtifacts", () => {
                const artifact1 = create("baseline_artifact", { id: 1 });
                const artifact2 = create("baseline_artifact", { id: 2 });

                beforeEach(() => store.mutations.addBaseArtifacts(state, [artifact1, artifact2]));

                it("adds given artifacts", () => {
                    expect(state.base_artifacts_by_id[1]).toEqual(artifact1);
                    expect(state.base_artifacts_by_id[2]).toEqual(artifact2);
                });
                it("updates first level base artifacts", () => {
                    expect(state.first_level_base_artifacts).toEqual([artifact1, artifact2]);
                });
            });

            describe("#addComparedToArtifacts", () => {
                const artifact1 = create("baseline_artifact", { id: 1 });
                const artifact2 = create("baseline_artifact", { id: 2 });

                beforeEach(() =>
                    store.mutations.addComparedToArtifacts(state, [artifact1, artifact2]));

                it("adds given artifacts", () => {
                    expect(state.compared_to_artifacts_by_id[1]).toEqual(artifact1);
                    expect(state.compared_to_artifacts_by_id[2]).toEqual(artifact2);
                });
                it("updates first level compared to artifacts", () => {
                    expect(state.first_level_compared_to_artifacts).toEqual([artifact1, artifact2]);
                });
            });

            describe("#updateStatistics", () => {
                beforeEach(() => {
                    const artifacts_comparison = create("artifacts_comparison", "empty", {
                        added: createList("baseline_artifact", 1),
                        removed: createList("baseline_artifact", 2),
                        modified: createList("baseline_artifact", 3)
                    });
                    store.mutations.updateStatistics(state, artifacts_comparison);
                });

                it("updates count statistics", () => {
                    expect(state.added_artifacts_count).toEqual(1);
                    expect(state.removed_artifacts_count).toEqual(2);
                    expect(state.modified_artifacts_count).toEqual(3);
                });

                describe("when comparison contains identical artifacts", () => {
                    describe("with same initial effort", () => {
                        beforeEach(() => {
                            const artifacts_comparison = create("artifacts_comparison", "empty", {
                                identical_or_modified: [
                                    {
                                        base: create("baseline_artifact", { initial_effort: 3 }),
                                        compared_to: create("baseline_artifact", {
                                            initial_effort: 3
                                        })
                                    }
                                ]
                            });
                            store.mutations.updateStatistics(state, artifacts_comparison);
                        });
                        it("does not modify initial effort statistics", () => {
                            expect(state.initial_effort_difference).toEqual(0);
                        });
                    });

                    describe("with not same initial effort", () => {
                        beforeEach(() => {
                            const artifacts_comparison = create("artifacts_comparison", "empty", {
                                identical_or_modified: [
                                    {
                                        base: create("baseline_artifact", { initial_effort: 3 }),
                                        compared_to: create("baseline_artifact", {
                                            initial_effort: 5
                                        })
                                    }
                                ]
                            });
                            store.mutations.updateStatistics(state, artifacts_comparison);
                        });
                        it("updates initial effort statistics", () => {
                            expect(state.initial_effort_difference).toEqual(2);
                        });
                    });
                });
            });
        });
    });

    describe("getters", () => {
        let state;
        beforeEach(() => (state = { ...store.state }));

        describe("#findBaseArtifactsByIds", () => {
            const artifact1 = create("baseline_artifact");
            const artifact2 = create("baseline_artifact");
            beforeEach(() =>
                (state.base_artifacts_by_id = {
                    1: artifact1,
                    2: artifact2
                }));
            it("returns all base artifacts with given ids", () => {
                expect(store.getters.findBaseArtifactsByIds(state)([1, 2])).toEqual([
                    artifact1,
                    artifact2
                ]);
            });
        });

        describe("#findComparedToArtifactsByIds", () => {
            const artifact1 = create("baseline_artifact");
            const artifact2 = create("baseline_artifact");
            beforeEach(() =>
                (state.compared_to_artifacts_by_id = {
                    1: artifact1,
                    2: artifact2
                }));
            it("returns all base artifacts with given ids", () => {
                expect(store.getters.findComparedToArtifactsByIds(state)([1, 2])).toEqual([
                    artifact1,
                    artifact2
                ]);
            });
        });
    });
});
