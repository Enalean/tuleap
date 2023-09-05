/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { ActionContext } from "vuex";
import type { RootState } from "../../type";
import type { NewCardPayload, NewRemainingEffortPayload, UpdateCardPayload } from "./type";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { RefreshCardMutationPayload, SwimlaneState } from "../type";
import type { Card, Swimlane, Tracker, User } from "../../../type";
import * as actions from "./card-actions";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { ListField, TextField } from "./api-artifact-type";

jest.mock("@tuleap/tlp-fetch");

describe("Card actions", () => {
    let context: ActionContext<SwimlaneState, RootState>;

    beforeEach(() => {
        jest.clearAllMocks();
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                user: {
                    user_id: 101,
                },
                trackers: [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                    {
                        id: 69,
                        title_field: { id: 123, is_string_field: true },
                        artifact_link_field: { id: 111 },
                    } as Tracker,
                ],
            } as RootState,
            getters: {
                have_possible_assignees_been_loaded_for_tracker: (): boolean => false,
            },
        } as unknown as ActionContext<SwimlaneState, RootState>;
    });

    describe("saveRemainingEffort", () => {
        it("saves the new value", async () => {
            const card: Card = { id: 123 } as Card;
            const new_remaining_effort: NewRemainingEffortPayload = {
                card,
                value: 42,
            };

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.saveRemainingEffort(context, new_remaining_effort);
            expect(context.commit).toHaveBeenCalledWith(
                "startSavingRemainingEffort",
                new_remaining_effort,
            );
            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/taskboard_cards/123`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    remaining_effort: 42,
                }),
            });
            expect(context.commit).toHaveBeenCalledWith("resetSavingRemainingEffort", card);
        });

        it("warns about error if any", async () => {
            const card: Card = { id: 123 } as Card;
            const new_remaining_effort: NewRemainingEffortPayload = {
                card,
                value: 42,
            };

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(tlpPatchMock, {});

            await actions.saveRemainingEffort(context, new_remaining_effort);

            expect(context.commit).toHaveBeenCalledWith("resetSavingRemainingEffort", card);
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleModalError",
                expect.anything(),
                { root: true },
            );
        });
    });

    describe("saveCard", () => {
        it("saves the new value", async () => {
            const card: Card = { id: 123, tracker_id: 1 } as Card;
            const tracker = {
                id: 1,
                title_field: { id: 1355, is_string_field: true },
                assigned_to_field: { id: 1356 },
            } as Tracker;
            const payload: UpdateCardPayload = {
                card,
                label: "Lorem",
                assignees: [{ id: 123 }] as User[],
                tracker: tracker,
            };

            const tlpPutMock = jest.spyOn(tlp_fetch, "put");
            const tlpGetMock = jest.spyOn(tlp_fetch, "get");

            const refreshed_card = { id: 123 } as Card;
            mockFetchSuccess(tlpGetMock, {
                return_json: refreshed_card,
            });

            await actions.saveCard(context, payload);

            expect(tlpPutMock).toHaveBeenCalledWith("/api/v1/artifacts/123", {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    values: [
                        {
                            field_id: 1355,
                            value: "Lorem",
                        } as TextField,
                        {
                            field_id: 1356,
                            bind_value_ids: [123],
                        } as ListField,
                    ],
                }),
            });
            expect(tlpGetMock).toHaveBeenCalledWith("/api/v1/taskboard_cards/123", {
                params: {
                    milestone_id: 42,
                },
            });
            expect(context.commit).toHaveBeenCalledWith("refreshCard", {
                refreshed_card,
            } as RefreshCardMutationPayload);
            expect(context.commit).toHaveBeenCalledWith("startSavingCard", card);
            expect(context.commit).toHaveBeenCalledWith("finishSavingCard", payload);
        });

        it("warns about error if any", async () => {
            const card: Card = { id: 123, tracker_id: 1 } as Card;
            const tracker = { id: 1, title_field: { id: 1355, is_string_field: true } } as Tracker;
            const payload: UpdateCardPayload = {
                card,
                label: "Lorem",
                assignees: [],
                tracker,
            };

            const tlpPutMock = jest.spyOn(tlp_fetch, "put");
            const error = new Error();
            tlpPutMock.mockRejectedValue(error);

            await actions.saveCard(context, payload);

            expect(context.commit).toHaveBeenCalledWith("startSavingCard", card);
            expect(context.commit).not.toHaveBeenCalledWith("finishSavingCard", payload);
            expect(context.commit).toHaveBeenCalledWith("resetSavingCard", card);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true,
            });
        });
    });

    describe("addCard", () => {
        it("Start the creation of a card", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { id: 74, tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                },
                label: "Lorem",
            } as NewCardPayload;

            await actions.addCard(context, payload);

            expect(context.commit).toHaveBeenCalledWith("startCreatingCard");
        });

        it("saves the card", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { id: 74, tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                },
                label: "Lorem",
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp_fetch, "post");

            await actions.addCard(context, payload);

            expect(tlpPostMock).toHaveBeenCalledWith("/api/v1/artifacts", {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    tracker: {
                        id: 69,
                    },
                    values: [
                        {
                            field_id: 123,
                            value: "Lorem",
                        },
                        {
                            field_id: 666,
                            bind_value_ids: [101],
                        },
                    ],
                }),
            });
        });

        it("Inject new card in store", async () => {
            const swimlane: Swimlane = { card: { id: 74, tracker_id: 42 } } as Swimlane;
            const payload: NewCardPayload = {
                swimlane: swimlane,
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                },
                label: "Lorem",
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPostMock, { return_json: { id: 1001 } });

            const tlpGetMock = jest.spyOn(tlp_fetch, "get");
            tlpGetMock.mockImplementation((uri: string): Promise<Response> => {
                if (uri === "/api/v1/artifacts/74") {
                    return Promise.resolve({
                        json: () => Promise.resolve({ values: [] }),
                    } as Response);
                }
                if (uri === "/api/v1/taskboard_cards/1001?milestone_id=42") {
                    return Promise.resolve({
                        json: () => Promise.resolve({ id: 1001, color: "fiesta-red" }),
                    } as Response);
                }
                throw new Error();
            });

            await actions.addCard(context, payload);

            expect(context.commit).toHaveBeenNthCalledWith(2, "addChildrenToSwimlane", {
                swimlane,
                children_cards: [
                    {
                        id: 1001,
                        color: "fiesta-red",
                        is_being_saved: true,
                        is_in_edit_mode: false,
                        is_just_saved: false,
                    } as Card,
                ],
            });
            expect(context.commit).toHaveBeenNthCalledWith(3, "cardIsHalfwayCreated");
        });

        it("warns about error if any", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                },
                label: "Lorem",
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp_fetch, "post");
            const error = new Error();
            tlpPostMock.mockRejectedValue(error);

            await actions.addCard(context, payload);

            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true,
            });
        });

        describe(`Given the card creation succeeded`, () => {
            let payload: NewCardPayload;
            let tlpGetMock: jest.SpyInstance;

            beforeEach(() => {
                payload = {
                    swimlane: { card: { id: 74, tracker_id: 42 } },
                    column: {
                        mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                    },
                    label: "Lorem",
                } as NewCardPayload;
                const tlpPostMock = jest.spyOn(tlp_fetch, "post");
                mockFetchSuccess(tlpPostMock, { return_json: { id: 1001 } });
                tlpGetMock = jest.spyOn(tlp_fetch, "get");
                mockFetchSuccess(tlpGetMock, {
                    headers: new Headers({ "Last-Modified": "Mon, 09 Dec 2019 10:11:35 GMT" }),
                    return_json: { values: [] },
                });
            });

            it("attach the card to the parent", async () => {
                const tlpPutMock = jest.spyOn(tlp_fetch, "put");

                await actions.addCard(context, payload);

                expect(tlpGetMock).toHaveBeenCalledWith("/api/v1/artifacts/74");
                expect(tlpPutMock).toHaveBeenCalledWith("/api/v1/artifacts/74", {
                    headers: {
                        "Content-Type": "application/json",
                        "If-Unmodified-Since": "Mon, 09 Dec 2019 10:11:35 GMT",
                    },
                    body: JSON.stringify({
                        values: [{ field_id: 103, links: [{ id: 1001, type: "_is_child" }] }],
                    }),
                });
            });

            it(`when it could not attach the card to its parent due to any other error, it will show an error modal`, async () => {
                const error = { response: { status: 500 } };
                jest.spyOn(tlp_fetch, "put").mockRejectedValue(error);

                await actions.addCard(context, payload);

                expect(context.dispatch).toHaveBeenCalledWith(
                    "error/handleModalError",
                    expect.any(Error),
                    {
                        root: true,
                    },
                );
            });
        });
    });

    describe("loadPossibleAssignees", () => {
        let tlpGetMock: jest.SpyInstance<Promise<Response>>;

        beforeEach(() => {
            tlpGetMock = jest.spyOn(tlp_fetch, "get");
        });

        it("Does nothing if the tracker has no assigned_to field", async () => {
            const tracker = {
                assigned_to_field: null,
            } as Tracker;

            await actions.loadPossibleAssignees(context, tracker);

            expect(tlpGetMock).not.toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalled();
        });

        it("Does nothing if potential assignees are already in cache", async () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            context.getters.have_possible_assignees_been_loaded_for_tracker = (): boolean => true;

            await actions.loadPossibleAssignees(context, tracker);

            expect(tlpGetMock).not.toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalled();
        });

        it("Loads assignees otherwise, and cast their ids to number", async () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            context.getters.have_possible_assignees_been_loaded_for_tracker = (): boolean => false;

            mockFetchSuccess(tlpGetMock, {
                return_json: [
                    { display_name: "John", id: "123" },
                    { display_name: "Steeve", id: "124" },
                    { display_name: "Bob", id: "125" },
                ],
            });

            await actions.loadPossibleAssignees(context, tracker);

            expect(tlpGetMock).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setPossibleAssigneesForFieldId", {
                assigned_to_field_id: 1234,
                users: [
                    {
                        id: 123,
                        display_name: "John",
                        text: "John",
                    },
                    {
                        id: 124,
                        display_name: "Steeve",
                        text: "Steeve",
                    },
                    {
                        id: 125,
                        display_name: "Bob",
                        text: "Bob",
                    },
                ],
            });
        });

        it("Error modal is shown on error", async () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            context.getters.have_possible_assignees_been_loaded_for_tracker = (): boolean => false;

            mockFetchError(tlpGetMock, {});

            await actions.loadPossibleAssignees(context, tracker);

            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleModalError",
                expect.anything(),
                { root: true },
            );
        });
    });
});
