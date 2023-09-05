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
 */

import type { Card, Swimlane } from "../../type";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { RecursiveGetInit } from "@tuleap/tlp-fetch";
import * as actions from "./swimlane-actions";
import { loadChildrenCards } from "./swimlane-actions";
import type { ActionContext } from "vuex";
import type { RefreshCardActionPayload, SwimlaneState } from "./type";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { RootState } from "../type";

describe("Swimlane state actions", () => {
    let context: ActionContext<SwimlaneState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            getters: {
                is_drop_accepted_in_target: (): boolean => true,
                is_there_at_least_one_children_to_display: (swimlane: Swimlane): boolean =>
                    swimlane.card.has_children,
            },
            rootState: {
                milestone_id: 42,
                user: {
                    user_id: 101,
                },
            } as RootState,
        } as unknown as ActionContext<SwimlaneState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
    });

    describe(`loadSwimlanes`, () => {
        it("Retrieves all top-level cards of the taskboard", async () => {
            await actions.loadSwimlanes(context);
            expect(context.commit).toHaveBeenCalledWith("beginLoadingSwimlanes");
            expect(context.commit).toHaveBeenCalledWith("endLoadingSwimlanes");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/taskboard/42/cards`, {
                params: { limit: 100 },
                getCollectionCallback: expect.any(Function),
            });
        });

        it("Stores the new swimlanes", async () => {
            tlpRecursiveGetMock = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockImplementation(
                    <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<T[]> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(
                            init.getCollectionCallback([{ id: 43 } as Card, { id: 44 } as Card]),
                        );
                    },
                );
            await actions.loadSwimlanes(context);
            expect(context.commit).toHaveBeenCalledWith("addSwimlanes", [
                {
                    card: {
                        id: 43,
                        is_in_edit_mode: false,
                        is_being_saved: false,
                        is_just_saved: false,
                    },
                    children_cards: [] as Card[],
                    is_loading_children_cards: false,
                } as Swimlane,
                {
                    card: {
                        id: 44,
                        is_in_edit_mode: false,
                        is_being_saved: false,
                        is_just_saved: false,
                    },
                    children_cards: [] as Card[],
                    is_loading_children_cards: false,
                } as Swimlane,
            ]);
        });

        it(`when top-level cards have children, it will load their children`, async () => {
            const card_with_children = {
                id: 43,
                has_children: true,
            } as Card;
            const other_card_with_children = {
                id: 44,
                has_children: true,
            } as Card;
            const card_without_children = {
                id: 45,
                has_children: false,
            } as Card;
            tlpRecursiveGetMock = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockImplementation(
                    <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<T[]> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(
                            init.getCollectionCallback([
                                card_with_children,
                                other_card_with_children,
                                card_without_children,
                            ]),
                        );
                    },
                );
            await actions.loadSwimlanes(context);
            expect(context.dispatch).toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: {
                        ...card_with_children,
                        is_in_edit_mode: false,
                        is_being_saved: false,
                        is_just_saved: false,
                    },
                }),
            );
            expect(context.dispatch).toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: {
                        ...other_card_with_children,
                        is_in_edit_mode: false,
                        is_being_saved: false,
                        is_just_saved: false,
                    },
                }),
            );
            expect(context.dispatch).not.toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: {
                        ...card_without_children,
                        is_in_edit_mode: false,
                        is_being_saved: false,
                        is_just_saved: false,
                    },
                }),
            );
        });

        it(`When there is a REST error, it will stop the loading flag and will show a global error`, async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);
            await actions.loadSwimlanes(context);
            expect(context.dispatch).toHaveBeenCalledTimes(1);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleGlobalError", error, {
                root: true,
            });
            expect(context.commit).toHaveBeenCalledWith("endLoadingSwimlanes");
        });
    });

    describe(`loadChildrenCards`, () => {
        let swimlane: Swimlane;
        beforeEach(() => {
            swimlane = {
                card: { id: 197 } as Card,
                children_cards: [],
                is_loading_children_cards: false,
            };
        });

        it(`Retrieves all children cards of a top-level card`, async () => {
            await actions.loadChildrenCards(context, swimlane);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingChildren", swimlane);
            expect(context.commit).toHaveBeenCalledWith("endLoadingChildren", swimlane);
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
                "/api/v1/taskboard_cards/197/children",
                {
                    params: {
                        milestone_id: 42,
                        limit: 100,
                    },
                    getCollectionCallback: expect.any(Function),
                },
            );
        });

        it(`Adds the new children cards to the swimlane in the store`, async () => {
            const children_cards = [{ id: 43 } as Card, { id: 44 } as Card];
            tlpRecursiveGetMock = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockImplementation(
                    <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<Array<T>> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(init.getCollectionCallback(children_cards));
                    },
                );

            await actions.loadChildrenCards(context, swimlane);
            expect(context.commit).toHaveBeenCalledWith("addChildrenToSwimlane", {
                swimlane,
                children_cards,
            });
        });

        it(`When there is a REST error, it will stop the loading flag and will show an error modal`, async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);
            await loadChildrenCards(context, swimlane);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true,
            });
            expect(context.commit).toHaveBeenCalledWith("endLoadingChildren", swimlane);
        });
    });

    describe("expandSwimlane", () => {
        it(`When the swimlane is expanded, the user pref is stored`, async () => {
            const swimlane: Swimlane = {
                card: { id: 69 } as Card,
            } as Swimlane;

            const tlpDeleteMock = jest.spyOn(tlp_fetch, "del");
            mockFetchSuccess(tlpDeleteMock, {});

            await actions.expandSwimlane(context, swimlane);
            expect(context.commit).toHaveBeenCalledWith("expandSwimlane", swimlane);
            expect(context.dispatch).toHaveBeenCalledWith(
                "user/deletePreference",
                { key: "plugin_taskboard_collapse_42_69" },
                { root: true },
            );
        });
    });

    describe("collapseSwimlane", () => {
        it(`When the swimlane is collapsed, the user pref is stored`, async () => {
            const swimlane: Swimlane = {
                card: { id: 69 } as Card,
            } as Swimlane;

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.collapseSwimlane(context, swimlane);
            expect(context.commit).toHaveBeenCalledWith("collapseSwimlane", swimlane);
            expect(context.dispatch).toHaveBeenCalledWith(
                "user/setPreference",
                { key: "plugin_taskboard_collapse_42_69", value: "1" },
                { root: true },
            );
        });
    });

    describe(`refreshCardAndParent()`, () => {
        describe(`Given a swimlane and a card that was just dropped in it, when it is a solo card`, () => {
            let swimlane: Swimlane, payload: RefreshCardActionPayload;
            beforeEach(() => {
                swimlane = {
                    card: { id: 104, label: "Solo card in state", has_children: false } as Card,
                    children_cards: [],
                    is_loading_children_cards: false,
                };
                payload = { swimlane, card: swimlane.card };
            });
            it(`will GET the card using the REST API and mutate the store`, async () => {
                const refreshed_card = {
                    id: 104,
                    label: "Refreshed solo card",
                    has_children: false,
                } as Card;
                const tlpGetMock = jest.spyOn(tlp_fetch, "get");
                mockFetchSuccess(tlpGetMock, { return_json: refreshed_card });

                await actions.refreshCardAndParent(context, payload);

                expect(tlpGetMock).toHaveBeenCalledWith("/api/v1/taskboard_cards/104", {
                    params: { milestone_id: 42 },
                });
                expect(context.commit).toHaveBeenCalledWith("refreshCard", {
                    refreshed_card,
                });
            });

            it(`when there is an error, it will open a modal`, async () => {
                const error = new Error();

                const tlpGetMock = jest.spyOn(tlp_fetch, "get");
                tlpGetMock.mockRejectedValue(error);

                await actions.refreshCardAndParent(context, payload);

                expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                    root: true,
                });
            });
        });

        describe(`Given a swimlane and a card that was just dropped in it, when is is a child card`, () => {
            let swimlane: Swimlane, payload: RefreshCardActionPayload;
            beforeEach(() => {
                const card = { id: 59, label: "Child card" } as Card;
                swimlane = {
                    card: { id: 78, label: "Parent card", has_children: true } as Card,
                    children_cards: [card],
                    is_loading_children_cards: false,
                };
                payload = { swimlane, card };
            });
            it(`will GET the card using the REST API and mutate the store with it
            and it will also GET the parent card using the REST API and mutate the store with it`, async () => {
                const refreshed_child_card = { id: 59, label: "Refreshed child card" } as Card;
                const refreshed_parent_card = {
                    id: 78,
                    label: "Refreshed parent card",
                    has_children: true,
                } as Card;
                const tlpGetMock = jest.spyOn(tlp_fetch, "get");
                tlpGetMock.mockResolvedValueOnce({
                    json: () => Promise.resolve(refreshed_child_card),
                } as Response);
                tlpGetMock.mockResolvedValueOnce({
                    json: () => Promise.resolve(refreshed_parent_card),
                } as Response);

                await actions.refreshCardAndParent(context, payload);

                expect(tlpGetMock).toHaveBeenCalledWith("/api/v1/taskboard_cards/59", {
                    params: { milestone_id: 42 },
                });
                expect(context.commit).toHaveBeenCalledWith("refreshCard", {
                    refreshed_card: refreshed_child_card,
                });

                expect(tlpGetMock).toHaveBeenCalledWith("/api/v1/taskboard_cards/78", {
                    params: { milestone_id: 42 },
                });
                expect(context.commit).toHaveBeenCalledWith("refreshCard", {
                    refreshed_card: refreshed_parent_card,
                });
            });

            it(`when there is an error, it will open a modal`, async () => {
                const error = new Error();

                const tlpGetMock = jest.spyOn(tlp_fetch, "get");
                tlpGetMock.mockRejectedValue(error);

                await actions.refreshCardAndParent(context, payload);

                expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                    root: true,
                });
            });
        });
    });
});
