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
                        init.getCollectionCallback([{ id: 43 } as Card, { id: 44 } as Card])
                    );
                }
            );
            await actions.loadSwimlanes(context);
            expect(context.commit).toHaveBeenCalledWith("addSwimlanes", [
                {
                    card: { id: 43 },
                    children_cards: [],
                    is_loading_children_cards: false
                },
                {
                    card: { id: 44 },
                    children_cards: [],
                    is_loading_children_cards: false
                }
            ]);
        });

        it(`when top-level cards have children, it will load their children`, async () => {
            const card_with_children = { id: 43, has_children: true } as Card;
            const other_card_with_children = { id: 44, has_children: true } as Card;
            const card_without_children = { id: 45, has_children: false } as Card;
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

        it(`Given a rest error,
            the error message is stored
            and the loading flag will be removed`, async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);
            await actions.loadSwimlanes(context);
            expect(context.dispatch).toHaveBeenCalledTimes(1);
            expect(context.dispatch).toHaveBeenCalledWith("error/handleErrorMessage", error, {
                root: true
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
                is_loading_children_cards: false
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

        it(`When there is a REST error, it will stop the loading flag
            but won't report the error for now`, async () => {
            tlpRecursiveGetMock.mockRejectedValue(new Error());
            await loadChildrenCards(context, swimlane);
            expect(context.commit).toHaveBeenCalledWith("endLoadingChildren", swimlane);
        });
    });
});
