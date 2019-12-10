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

import { ActionContext } from "vuex";
import { RootState } from "../../type";
import { UpdateCardPayload, NewRemainingEffortPayload, NewCardPayload } from "./type";
import * as tlp from "tlp";
import { SwimlaneState } from "../type";
import { Card, Swimlane, Tracker } from "../../../type";
import * as actions from "./card-actions";
import {
    mockFetchError,
    mockFetchSuccess
} from "../../../../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper";

jest.mock("tlp");

describe("Card actions", () => {
    let context: ActionContext<SwimlaneState, RootState>;

    beforeEach(() => {
        jest.clearAllMocks();
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                user: {
                    user_id: 101
                },
                trackers: [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 }
                    } as Tracker,
                    {
                        id: 69,
                        title_field: { id: 123, is_string_field: true },
                        artifact_link_field: { id: 111 }
                    } as Tracker
                ]
            } as RootState
        } as unknown) as ActionContext<SwimlaneState, RootState>;
    });

    describe("saveRemainingEffort", () => {
        it("saves the new value", async () => {
            const card: Card = { id: 123 } as Card;
            const new_remaining_effort: NewRemainingEffortPayload = {
                card,
                value: 42
            };

            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.saveRemainingEffort(context, new_remaining_effort);
            expect(context.commit).toHaveBeenCalledWith("startSavingRemainingEffort", card);
            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/taskboard_cards/123`, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    remaining_effort: 42
                })
            });
            expect(context.commit).toHaveBeenCalledWith(
                "finishSavingRemainingEffort",
                new_remaining_effort
            );
        });

        it("warns about error if any", async () => {
            const card: Card = { id: 123 } as Card;
            const new_remaining_effort: NewRemainingEffortPayload = {
                card,
                value: 42
            };

            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatchMock, {});

            await actions.saveRemainingEffort(context, new_remaining_effort);

            expect(context.commit).not.toHaveBeenCalledWith(
                "finishSavingRemainingEffort",
                new_remaining_effort
            );
            expect(context.commit).toHaveBeenCalledWith("resetSavingRemainingEffort", card);
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleModalError",
                expect.anything(),
                { root: true }
            );
        });
    });

    describe("saveCard", () => {
        it("saves the new value", async () => {
            const card: Card = { id: 123, tracker_id: 1 } as Card;
            const tracker = { id: 1, title_field: { id: 1355, is_string_field: true } } as Tracker;
            const payload: UpdateCardPayload = {
                card,
                label: "Lorem",
                tracker: tracker
            };

            const tlpPutMock = jest.spyOn(tlp, "put");

            await actions.saveCard(context, payload);

            expect(tlpPutMock).toHaveBeenCalledWith("/api/v1/artifacts/123", {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    values: [
                        {
                            field_id: 1355,
                            value: "Lorem"
                        }
                    ]
                })
            });
            expect(context.commit).toHaveBeenCalledWith("startSavingCard", card);
            expect(context.commit).toHaveBeenCalledWith("finishSavingCard", payload);
        });

        it("warns about error if any", async () => {
            const card: Card = { id: 123, tracker_id: 1 } as Card;
            const tracker = { id: 1, title_field: { id: 1355, is_string_field: true } } as Tracker;
            const payload: UpdateCardPayload = {
                card,
                label: "Lorem",
                tracker
            };

            const tlpPutMock = jest.spyOn(tlp, "put");
            const error = new Error();
            tlpPutMock.mockRejectedValue(error);

            await actions.saveCard(context, payload);

            expect(context.commit).toHaveBeenCalledWith("startSavingCard", card);
            expect(context.commit).not.toHaveBeenCalledWith("finishSavingCard", payload);
            expect(context.commit).toHaveBeenCalledWith("resetSavingCard", card);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true
            });
        });
    });

    describe("addCard", () => {
        it("Start the creation of a card", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { id: 74, tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }]
                },
                label: "Lorem"
            } as NewCardPayload;

            await actions.addCard(context, payload);

            expect(context.commit).toHaveBeenCalledWith("startCreatingCard");
        });

        it("saves the card", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { id: 74, tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }]
                },
                label: "Lorem"
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp, "post");

            await actions.addCard(context, payload);

            expect(tlpPostMock).toHaveBeenCalledWith("/api/v1/artifacts", {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    tracker: {
                        id: 69
                    },
                    values: [
                        {
                            field_id: 123,
                            value: "Lorem"
                        },
                        {
                            field_id: 666,
                            bind_value_ids: [101]
                        }
                    ]
                })
            });
        });

        it("Inject new card in store", async () => {
            const swimlane: Swimlane = { card: { id: 74, tracker_id: 42 } } as Swimlane;
            const payload: NewCardPayload = {
                swimlane: swimlane,
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }]
                },
                label: "Lorem"
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPostMock, { return_json: { id: 1001 } });

            const tlpGetMock = jest.spyOn(tlp, "get");
            tlpGetMock.mockImplementation(
                (uri: string): Promise<Response> => {
                    if (uri === "/api/v1/artifacts/74") {
                        return Promise.resolve({
                            json: () => Promise.resolve({ values: [] })
                        } as Response);
                    }
                    if (uri === "/api/v1/taskboard_cards/1001?milestone_id=42") {
                        return Promise.resolve({
                            json: () => Promise.resolve({ id: 1001, color: "fiesta-red" })
                        } as Response);
                    }
                    throw new Error();
                }
            );

            await actions.addCard(context, payload);

            expect(context.commit).toHaveBeenNthCalledWith(2, "addChildrenToSwimlane", {
                swimlane,
                children_cards: [
                    {
                        id: 1001,
                        color: "fiesta-red",
                        is_being_saved: true,
                        is_in_edit_mode: false,
                        is_just_saved: false
                    } as Card
                ]
            });
            expect(context.commit).toHaveBeenNthCalledWith(3, "cardIsHalfwayCreated");
        });

        it("attach the card to the parent", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { id: 74, tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }]
                },
                label: "Lorem"
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPostMock, { return_json: { id: 1001 } });

            const tlpGetMock = jest.spyOn(tlp, "get");
            mockFetchSuccess(tlpGetMock, { return_json: { values: [] } });

            const tlpPutMock = jest.spyOn(tlp, "put");

            await actions.addCard(context, payload);

            expect(tlpGetMock).toHaveBeenCalledWith("/api/v1/artifacts/74");
            expect(tlpPutMock).toHaveBeenCalledWith("/api/v1/artifacts/74", {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    values: [
                        {
                            field_id: 103,
                            links: [
                                {
                                    id: 1001,
                                    type: "_is_child"
                                }
                            ]
                        }
                    ]
                })
            });
        });

        it("warns about error if any", async () => {
            const payload: NewCardPayload = {
                swimlane: { card: { tracker_id: 42 } },
                column: {
                    mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }]
                },
                label: "Lorem"
            } as NewCardPayload;

            const tlpPostMock = jest.spyOn(tlp, "post");
            const error = new Error();
            tlpPostMock.mockRejectedValue(error);

            await actions.addCard(context, payload);

            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true
            });
        });
    });
});
