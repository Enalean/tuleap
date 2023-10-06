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

import store from "./semantics";
import * as rest_querier from "../api/rest-querier";

describe("Semantics store:", () => {
    let state;

    beforeEach(() => {
        state = { ...store.state };
    });

    describe("actions", () => {
        let context;

        beforeEach(() => {
            const commit = jest.fn();
            context = { state, commit };
        });

        describe("#loadByTrackerId", () => {
            let getTracker;

            beforeEach(() => {
                getTracker = jest.spyOn(rest_querier, "getTracker");
            });

            describe("when currently loading", () => {
                beforeEach(() => {
                    store.state.is_field_by_tracker_id_loading[1] = true;
                    store.actions.loadByTrackerId(context, 1);
                });

                it("does not load semantics", () => {
                    expect(getTracker).not.toHaveBeenCalled();
                });
            });

            describe("when not currently loading", () => {
                let resolveGetTracker;
                let rejectGetTracker;
                let action_promise;

                beforeEach(() => {
                    getTracker.mockReturnValue(
                        new Promise((resolve, reject) => {
                            resolveGetTracker = resolve;
                            rejectGetTracker = reject;
                        }),
                    );

                    state.is_field_by_tracker_id_loading[1] = false;
                    action_promise = store.actions.loadByTrackerId(context, 1);
                });

                it("start loading", () => {
                    expect(context.commit).toHaveBeenCalledWith("startLoading", 1);
                });

                it("loads semantics", () => {
                    expect(getTracker).toHaveBeenCalled();
                });

                describe("when getTracker() is resolved", () => {
                    const tracker = { id: 9 };
                    beforeEach(() => {
                        resolveGetTracker(tracker);
                        return action_promise;
                    });

                    it("updates fields with returned tracker", () => {
                        expect(context.commit).toHaveBeenCalledWith("update", tracker);
                    });
                    it("stop loading", () => {
                        expect(context.commit).toHaveBeenCalledWith("stopLoading", 1);
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

                    it("does not update fields", () => {
                        expect(context.commit).not.toHaveBeenCalledWith(
                            "update",
                            expect.anything(),
                        );
                    });
                    it("stop loading", () => {
                        expect(context.commit).toHaveBeenCalledWith("stopLoading", 1);
                    });
                });
            });
        });
    });

    describe("mutations", () => {
        describe("#startLoading", () => {
            beforeEach(() => {
                store.mutations.startLoading(state, 1);
            });

            it("updates state", () => {
                expect(state.is_field_by_tracker_id_loading[1]).toBeTruthy();
            });
        });

        describe("#update", () => {
            describe("when tracker has semantics defined", () => {
                const description_field = {
                    field_id: 22,
                    label: "Description",
                };

                beforeEach(() => {
                    store.mutations.update(state, {
                        id: 1,
                        fields: [description_field],
                        semantics: {
                            description: {
                                field_id: 22,
                            },
                        },
                    });
                });

                it("updates state with semantic field", () => {
                    expect(state.fields_by_tracker_id).toEqual({
                        1: {
                            description: description_field,
                        },
                    });
                });
            });

            describe("when tracker has no semantic", () => {
                beforeEach(() => {
                    store.mutations.update(state, { id: 1 });
                });

                it("updates state with no semantic field", () => {
                    expect(state.fields_by_tracker_id).toEqual({
                        1: {},
                    });
                });
            });
        });
    });

    describe("getters", () => {
        let getters = {
            field_label: () => {},
        };

        describe("#is_field_label_available", () => {
            describe("when field is loading", () => {
                beforeEach(() => {
                    state.is_field_by_tracker_id_loading[1] = true;
                });
                it("returns false", () => {
                    expect(
                        store.getters.is_field_label_available(state, getters)(1, "description"),
                    ).toBeFalsy();
                });
            });

            describe("when field is not loading", () => {
                let getterFieldLabel;
                beforeEach(() => {
                    state.is_field_by_tracker_id_loading[1] = false;
                    getterFieldLabel = jest.spyOn(getters, "field_label");
                });

                describe("when there is a field", () => {
                    beforeEach(() => {
                        getterFieldLabel.mockReturnValue("My description");
                    });
                    it("returns true", () => {
                        expect(
                            store.getters.is_field_label_available(state, getters)(
                                1,
                                "description",
                            ),
                        ).toBeTruthy();
                    });
                });

                describe("when there is no field", () => {
                    beforeEach(() => {
                        getterFieldLabel.mockReturnValue(null);
                    });
                    it("returns false", () => {
                        expect(
                            store.getters.is_field_label_available(state, getters)(
                                1,
                                "description",
                            ),
                        ).toBeFalsy();
                    });
                });
            });
        });

        describe("#field_label", () => {
            describe("when tracker semantics not loaded yet", () => {
                beforeEach(() => {
                    state.fields_by_tracker_id = {};
                });
                it("returns corresponding semantic label of given tracker", () => {
                    expect(store.getters.field_label(state)(1, "description")).toBeNull();
                });
            });
            describe("when given semantic does not exist on given tracker", () => {
                beforeEach(() => {
                    state.fields_by_tracker_id = {
                        1: { title: { label: "My title" } },
                    };
                });
                it("returns corresponding semantic label of given tracker", () => {
                    expect(store.getters.field_label(state)(1, "description")).toBeNull();
                });
            });
            beforeEach(() => {
                state.fields_by_tracker_id = {
                    1: { description: { label: "My description" } },
                };
            });
            it("returns corresponding semantic label of given tracker", () => {
                expect(store.getters.field_label(state)(1, "description")).toBe("My description");
            });
        });
    });
});
