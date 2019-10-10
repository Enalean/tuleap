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

import { Card, RootState, Swimlane } from "../../type";
import * as tlp from "tlp";
import * as actions from "./swimlane-actions";
import { RecursiveGetInit } from "tlp";
import { ActionContext } from "vuex";
import { SwimlaneState } from "./type";
import { loadChildrenCards } from "./swimlane-actions";

jest.mock("tlp");

describe("Swimlane state actions", () => {
    let context: ActionContext<SwimlaneState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42
            } as RootState
        } as unknown) as ActionContext<SwimlaneState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
    });

    describe(`loadSwimlanes`, () => {
        it("Retrieves all top-level cards of the taskboard", async () => {
            await actions.loadSwimlanes(context);
            expect(context.commit).toHaveBeenCalledWith("beginLoadingSwimlanes");
            expect(context.commit).toHaveBeenCalledWith("endLoadingSwimlanes");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/taskboard/42/cards`, {
                params: { limit: 100 },
                getCollectionCallback: expect.any(Function)
            });
        });

        it("Stores the new swimlanes", async () => {
            tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<T[]> => {
                    if (!init || !init.getCollectionCallback) {
                        throw new Error();
                    }

                    return Promise.resolve(
                        init.getCollectionCallback([
                            { id: 43, is_open: true } as Card,
                            { id: 44, is_open: true } as Card
                        ])
                    );
                }
            );
            await actions.loadSwimlanes(context);
            expect(context.commit).toHaveBeenCalledWith("addSwimlanes", [
                {
                    card: { id: 43, is_open: true },
                    children_cards: [],
                    is_loading_children_cards: false,
                    is_collapsed: false
                },
                {
                    card: { id: 44, is_open: true },
                    children_cards: [],
                    is_loading_children_cards: false,
                    is_collapsed: false
                }
            ]);
        });

        it("Collapses the new swimlanes when the card is closed", async () => {
            tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<T[]> => {
                    if (!init || !init.getCollectionCallback) {
                        throw new Error();
                    }

                    return Promise.resolve(
                        init.getCollectionCallback([{ id: 43, is_open: false } as Card])
                    );
                }
            );
            await actions.loadSwimlanes(context);
            expect(context.commit).toHaveBeenCalledWith("addSwimlanes", [
                {
                    card: { id: 43, is_open: false },
                    children_cards: [],
                    is_loading_children_cards: false,
                    is_collapsed: true
                }
            ]);
        });

        it(`when top-level cards have children, it will load their children`, async () => {
            const card_with_children = { id: 43, is_open: true, has_children: true } as Card;
            const other_card_with_children = { id: 44, is_open: true, has_children: true } as Card;
            const card_without_children = { id: 45, is_open: true, has_children: false } as Card;
            tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<T[]> => {
                    if (!init || !init.getCollectionCallback) {
                        throw new Error();
                    }

                    return Promise.resolve(
                        init.getCollectionCallback([
                            card_with_children,
                            other_card_with_children,
                            card_without_children
                        ])
                    );
                }
            );
            await actions.loadSwimlanes(context);
            expect(context.dispatch).toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: card_with_children
                })
            );
            expect(context.dispatch).toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: other_card_with_children
                })
            );
            expect(context.dispatch).not.toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: card_without_children
                })
            );
        });

        it(`when top-level card is closed and has children, it will NOT load its children`, async () => {
            const card_with_children = { id: 43, is_open: false, has_children: true } as Card;
            tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<T[]> => {
                    if (!init || !init.getCollectionCallback) {
                        throw new Error();
                    }

                    return Promise.resolve(init.getCollectionCallback([card_with_children]));
                }
            );
            await actions.loadSwimlanes(context);
            expect(context.dispatch).not.toHaveBeenCalledWith(
                "loadChildrenCards",
                expect.objectContaining({
                    card: card_with_children
                })
            );
        });

        it(`When there is a REST error, it will stop the loading flag and will show a global error`, async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);
            await actions.loadSwimlanes(context);
            expect(context.dispatch).toHaveBeenCalledTimes(1);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleGlobalError", error, {
                root: true
            });
            expect(context.commit).toHaveBeenCalledWith("endLoadingSwimlanes");
        });
    });

    describe(`loadChildrenCards`, () => {
        let swimlane: Swimlane;
        beforeEach(() => {
            swimlane = {
                card: { id: 197, is_open: true } as Card,
                children_cards: [],
                is_loading_children_cards: false,
                is_collapsed: false
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
                        limit: 100
                    },
                    getCollectionCallback: expect.any(Function)
                }
            );
        });

        it(`Adds the new children cards to the swimlane in the store`, async () => {
            const children_cards = [{ id: 43 } as Card, { id: 44 } as Card];
            tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<Array<T>> => {
                    if (!init || !init.getCollectionCallback) {
                        throw new Error();
                    }

                    return Promise.resolve(init.getCollectionCallback(children_cards));
                }
            );

            await actions.loadChildrenCards(context, swimlane);
            expect(context.commit).toHaveBeenCalledWith("addChildrenToSwimlane", {
                swimlane,
                children_cards
            });
        });

        it(`When there is a REST error, it will stop the loading flag and will show an error modal`, async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);
            await loadChildrenCards(context, swimlane);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true
            });
            expect(context.commit).toHaveBeenCalledWith("endLoadingChildren", swimlane);
        });
    });

    describe("expandSwimlane", () => {
        it(`When the swimlane card does NOT have children
            Then the children are not automatically loaded`, () => {
            const swimlane: Swimlane = {
                card: { has_children: false } as Card
            } as Swimlane;
            actions.expandSwimlane(context, swimlane);
            expect(context.dispatch).not.toHaveBeenCalledWith("loadChildrenCards", swimlane);
            expect(context.commit).toHaveBeenCalledWith("expandSwimlane", swimlane);
        });

        it(`When the swimlane card has children and they have NOT already been loaded
            Then the children are automatically loaded`, () => {
            const swimlane: Swimlane = {
                card: { has_children: true } as Card,
                children_cards: [] as Card[]
            } as Swimlane;
            actions.expandSwimlane(context, swimlane);
            expect(context.dispatch).toHaveBeenCalledWith("loadChildrenCards", swimlane);
            expect(context.commit).toHaveBeenCalledWith("expandSwimlane", swimlane);
        });

        it(`When the swimlane card has children and they have already been loaded
            Then the children are not automatically loaded`, () => {
            const swimlane: Swimlane = {
                card: { has_children: true } as Card,
                children_cards: [{ id: 123 } as Card]
            } as Swimlane;
            actions.expandSwimlane(context, swimlane);
            expect(context.dispatch).not.toHaveBeenCalledWith("loadChildrenCards", swimlane);
            expect(context.commit).toHaveBeenCalledWith("expandSwimlane", swimlane);
        });
    });
});
