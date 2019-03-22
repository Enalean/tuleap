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

import store from "./index.js";
import { create } from "../support/factories";
import { restore, rewire$getTracker } from "../api/rest-querier";

describe("Store:", () => {
    let state;

    beforeEach(() => {
        state = { ...store.state };
    });

    describe("actions", () => {
        let context;

        beforeEach(() => {
            const commit = jasmine.createSpy("commit");
            context = { state, commit };
        });

        describe("#loadSemanticFields", () => {
            let getTracker;

            beforeEach(() => {
                getTracker = jasmine.createSpy("getTracker");
                rewire$getTracker(getTracker);
            });

            afterEach(() => restore);

            describe("when semantic currently loading", () => {
                beforeEach(() => {
                    store.state.is_semantic_fields_by_tracker_id_loading[1] = true;
                    store.actions.loadSemanticFields(context, 1);
                });

                it("does not load semantics", () => {
                    expect(getTracker).not.toHaveBeenCalled();
                });
            });

            describe("when semantic not currently loading", () => {
                let resolveGetTracker;
                let rejectGetTracker;
                let action_promise;

                beforeEach(() => {
                    getTracker.and.returnValue(
                        new Promise((resolve, reject) => {
                            resolveGetTracker = resolve;
                            rejectGetTracker = reject;
                        })
                    );

                    state.is_semantic_fields_by_tracker_id_loading[1] = false;
                    action_promise = store.actions.loadSemanticFields(context, 1);
                });

                it("start semantic loading", () => {
                    expect(context.commit).toHaveBeenCalledWith("startSemanticFieldsLoading", 1);
                });

                it("loads semantics", () => {
                    expect(getTracker).toHaveBeenCalled();
                });

                describe("when getTracker() is resolved", () => {
                    const tracker = create("tracker");
                    beforeEach(() => {
                        resolveGetTracker(tracker);
                        return action_promise;
                    });

                    it("updates semantic fields with returned tracker", () => {
                        expect(context.commit).toHaveBeenCalledWith(
                            "updateSemanticFields",
                            tracker
                        );
                    });
                    it("stop semantic loading", () => {
                        expect(context.commit).toHaveBeenCalledWith("stopSemanticFieldsLoading", 1);
                    });
                });

                describe("when getTracker() is failed", () => {
                    beforeEach(async () => {
                        rejectGetTracker();
                        try {
                            await action_promise;
                        } catch (e) {
                            // Expected
                        }
                    });

                    it("does not update semantic fields", () => {
                        expect(context.commit).not.toHaveBeenCalledWith(
                            "updateSemanticFields",
                            jasmine.anything()
                        );
                    });
                    it("stop semantic loading", () => {
                        expect(context.commit).toHaveBeenCalledWith("stopSemanticFieldsLoading", 1);
                    });
                });
            });
        });
    });

    describe("mutations", () => {
        describe("#startSemanticFieldsLoading", () => {
            beforeEach(() => {
                store.mutations.startSemanticFieldsLoading(state, 1);
            });

            it("updates state", () => {
                expect(state.is_semantic_fields_by_tracker_id_loading[1]).toBeTruthy();
            });
        });

        describe("#updateSemanticFields", () => {
            describe("when tracker has semantics defined", () => {
                const description_field = create("field", {
                    field_id: 22,
                    label: "Description"
                });

                beforeEach(() => {
                    store.mutations.updateSemanticFields(
                        state,
                        create("tracker", {
                            id: 1,
                            fields: [description_field],
                            semantics: {
                                description: {
                                    field_id: 22
                                }
                            }
                        })
                    );
                });

                it("updates state with semantic field", () => {
                    expect(state.semantic_fields_by_tracker_id).toEqual({
                        1: {
                            description: description_field
                        }
                    });
                });
            });

            describe("when tracker has no semantic", () => {
                beforeEach(() => {
                    store.mutations.updateSemanticFields(
                        state,
                        create("tracker", "without_semantic", { id: 1 })
                    );
                });

                it("updates state with no semantic field", () => {
                    expect(state.semantic_fields_by_tracker_id).toEqual({
                        1: {}
                    });
                });
            });
        });
    });
});
