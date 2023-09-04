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
 */
import type { ActionContext } from "vuex";
import * as actions from "./drag-drop-actions";
import * as item_finder from "../../helpers/html-to-item";
import * as card_positioner from "../../helpers/cards-reordering";
import type {
    HandleDropPayload,
    MoveCardsPayload,
    ReorderCardsPayload,
    SwimlaneState,
} from "./type";
import type { RootState } from "../type";
import type { Card, CardPosition, ColumnDefinition, Swimlane } from "../../type";
import { AFTER, BEFORE } from "../../type";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

jest.mock("@tuleap/tlp-fetch");

function createElement(): HTMLElement {
    const local_document = document.implementation.createHTMLDocument();
    return local_document.createElement("div");
}

function createPayload(): HandleDropPayload {
    const dropped_card = createElement();
    const target_cell = createElement();
    const source_cell = createElement();
    const sibling_card = createElement();
    return { dropped_card, target_cell, source_cell, sibling_card };
}

describe(`drag-drop-actions`, () => {
    let context: ActionContext<SwimlaneState, RootState>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            getters: {
                cards_in_cell: jest.fn(),
                is_there_at_least_one_children_to_display: (swimlane: Swimlane): boolean =>
                    swimlane.card.has_children,
            },
            rootGetters: {
                column_and_swimlane_of_cell: jest.fn(),
            },
            dispatch: jest.fn(),
            rootState: {} as RootState,
        } as unknown as ActionContext<SwimlaneState, RootState>;
    });

    describe(`handleDrop()`, () => {
        it(`When the dropped card has been dropped in another swimlane,
            it will do nothing`, async () => {
            const hasBeenDropped = jest
                .spyOn(item_finder, "isDraggedOverAnotherSwimlane")
                .mockReturnValue(true);

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(hasBeenDropped).toHaveBeenCalledWith(payload.target_cell, payload.source_cell);
            expect(context.dispatch).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`When the swimlane of the target cell can't be found,
            it will do nothing`, async () => {
            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);

            context.rootGetters.column_and_swimlane_of_cell.mockImplementation(() => {
                return { swimlane: undefined, column: { id: 31 } as ColumnDefinition };
            });
            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(context.rootGetters.column_and_swimlane_of_cell).toHaveBeenCalledWith(
                payload.target_cell,
            );
            expect(context.dispatch).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`When the column of the target cell can't be found,
                it will do nothing`, async () => {
            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);

            context.rootGetters.column_and_swimlane_of_cell.mockImplementation(() => {
                return { swimlane: { card: { id: 543 } } as Swimlane, column: undefined };
            });
            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(context.rootGetters.column_and_swimlane_of_cell).toHaveBeenCalledWith(
                payload.target_cell,
            );
            expect(context.dispatch).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`When the dropped card can't be found in the state,
            it will do nothing`, async () => {
            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const swimlane = { card: { id: 543, has_children: true } } as Swimlane;
            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });

            const getCard = jest.spyOn(item_finder, "getCardFromSwimlane").mockReturnValue(null);

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(getCard).toHaveBeenCalledWith(swimlane, payload.dropped_card);
            expect(context.dispatch).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`When there is no sibling card,
            it will reorder cards in the cell`, async () => {
            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const swimlane = { card: { id: 543, has_children: true } } as Swimlane;
            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });
            const card = { id: 667, label: "Do the stuff" } as Card;
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(null);
            const before_sibling = { id: 778, label: "Documentation" };
            context.getters.cards_in_cell.mockReturnValue([before_sibling, card]);

            const position = {
                ids: [667],
                direction: AFTER,
                compared_to: 778,
            };
            jest.spyOn(card_positioner, "getCardPosition").mockReturnValue(position);

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(context.dispatch).toHaveBeenCalledWith("reorderCardsInCell", {
                swimlane,
                column,
                position,
            });
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`Given a dropped card and a target cell,
            it will reorder cards in the cell`, async () => {
            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const swimlane = { card: { id: 543, has_children: true } } as Swimlane;
            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });
            const card = { id: 667, label: "Do the stuff" } as Card;
            const sibling = { id: 778, label: "Documentation" } as Card;
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(sibling);
            context.getters.cards_in_cell.mockReturnValue([sibling, card]);

            const position = {
                ids: [667],
                direction: BEFORE,
                compared_to: 778,
            };
            jest.spyOn(card_positioner, "getCardPosition").mockReturnValue(position);

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(context.dispatch).toHaveBeenCalledWith("reorderCardsInCell", {
                swimlane,
                column,
                position,
            });
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`Given a dropped card, a target cell but no children inside,
            it will only change the card of column`, async () => {
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const swimlane = { card: { id: 543, has_children: true } } as Swimlane;

            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });

            const card = { id: 667, label: "Do the stuff" } as Card;
            const sibling = null;

            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(false);
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(sibling);

            context.getters.cards_in_cell.mockReturnValue([]);

            jest.spyOn(card_positioner, "getCardPosition");

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(card_positioner.getCardPosition).not.toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith("moveCardToCell", {
                card,
                swimlane,
                column,
            });
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`Given a dropped card, a target cell with some children inside,
            it will change the card of column and put it at the given position`, async () => {
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const swimlane = { card: { id: 543, has_children: true } } as Swimlane;

            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });

            const card = { id: 667, label: "Do the stuff" } as Card;
            const sibling = { id: 778, label: "Documentation" } as Card;

            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(false);
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(sibling);

            context.getters.cards_in_cell.mockReturnValue([sibling]);

            const position = {
                ids: [667],
                direction: BEFORE,
                compared_to: 778,
            };

            jest.spyOn(card_positioner, "getCardPosition");

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(card_positioner.getCardPosition).toHaveBeenCalledWith(card, sibling, [sibling]);
            expect(context.dispatch).toHaveBeenCalledWith("moveCardToCell", {
                card,
                swimlane,
                column,
                position,
            });
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`Given a dropped card in a solo card swimlane,
            it will change the card of column`, async () => {
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const card = { id: 667, label: "Do the stuff" } as Card;
            const swimlane = { card, children_cards: [] as Card[] } as Swimlane;

            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });

            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(false);
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(null);

            context.getters.cards_in_cell.mockReturnValue([]);

            jest.spyOn(card_positioner, "getCardPosition");

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(card_positioner.getCardPosition).not.toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith("moveCardToCell", {
                card,
                swimlane,
                column,
            });
            expect(context.commit).toHaveBeenCalledWith("unsetDropZoneRejectingDrop");
        });

        it(`Given a solo card
            When it has been dragged over some different containers and dropped in the source one
            Then it should not try to reorder the cards`, async () => {
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const card = { id: 667, label: "Do the stuff", has_children: false } as Card;
            const swimlane = { card } as Swimlane;

            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });

            const sibling = null;

            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(sibling);

            context.getters.cards_in_cell.mockReturnValue([card]); // Card is alone in its cell

            jest.spyOn(card_positioner, "getCardPosition");

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(card_positioner.getCardPosition).not.toHaveBeenCalled();
            expect(context.dispatch).not.toHaveBeenCalledWith();
        });

        it(`Given a child card alone in a cell
            When it has been dragged over some different containers and dropped in the source one
            Then it should not try to reorder the cards`, async () => {
            const column = { id: 31, label: "Todo" } as ColumnDefinition;
            const swimlane = { card: { id: 666, has_children: true } } as Swimlane;
            const card = { id: 667, label: "Do the stuff", has_children: false } as Card;

            context.rootGetters.column_and_swimlane_of_cell.mockReturnValue({ column, swimlane });

            const sibling = null;

            jest.spyOn(item_finder, "isDraggedOverTheSourceCell").mockReturnValue(true);
            jest.spyOn(item_finder, "getCardFromSwimlane")
                .mockReturnValueOnce(card)
                .mockReturnValueOnce(sibling);

            context.getters.cards_in_cell.mockReturnValue([card]); // Card is alone in its cell

            jest.spyOn(card_positioner, "getCardPosition");

            const payload = createPayload();
            await actions.handleDrop(context, payload);

            expect(card_positioner.getCardPosition).not.toHaveBeenCalled();
            expect(context.dispatch).not.toHaveBeenCalledWith();
        });
    });

    describe("reorderCardsInCell", () => {
        const card_to_move = { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card;
        const swimlane: Swimlane = {
            card: { id: 86 },
            children_cards: [
                { id: 100, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                card_to_move,
            ],
        } as Swimlane;

        const column: ColumnDefinition = {
            id: 42,
        } as ColumnDefinition;

        const position: CardPosition = {
            ids: [card_to_move.id],
            direction: BEFORE,
            compared_to: 100,
        };

        const payload = {
            swimlane,
            column,
            position,
        } as ReorderCardsPayload;

        it("The new position of the card is stored and the cards are reordered", async () => {
            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatchMock, {});
            await actions.reorderCardsInCell(context, payload);

            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/taskboard_cells/86/column/42`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    order: {
                        ids: [102],
                        direction: "before",
                        compared_to: 100,
                    },
                }),
            });

            expect(context.commit).toHaveBeenCalledWith("changeCardPosition", payload);
        });

        it("A modal opens on error", async () => {
            const error = new Error();

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            tlpPatchMock.mockRejectedValue(error);

            await actions.reorderCardsInCell(context, payload);

            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true,
            });
        });
    });

    describe("moveCardToCell", () => {
        let column: ColumnDefinition;

        beforeEach(() => {
            column = { id: 42 } as ColumnDefinition;
        });

        it(`when a child card was moved,
            it will PATCH the new cell,
            move the card to its new column
            and refresh the card and its parent`, async () => {
            const card_to_move = {
                id: 102,
                tracker_id: 7,
                mapped_list_value: { id: 49 },
                has_children: true,
            } as Card;
            const swimlane = {
                card: { id: 86 },
                children_cards: [
                    { id: 100, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                    card_to_move,
                ],
            } as Swimlane;
            const payload: MoveCardsPayload = { swimlane, column, card: card_to_move };

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.moveCardToCell(context, payload);

            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/taskboard_cells/86/column/42`, {
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ add: card_to_move.id }),
            });

            expect(context.dispatch).toHaveBeenCalledWith("refreshCardAndParent", {
                swimlane,
                card: card_to_move,
            });
            expect(context.commit).toHaveBeenCalledWith("moveCardToColumn", payload);
        });

        it(`when a solo card was moved,
            it will PATCH the new cell,
            move the card to its new column
            and refresh the card`, async () => {
            const card_to_move = {
                id: 102,
                tracker_id: 7,
                mapped_list_value: { id: 49 },
                has_children: false,
            } as Card;
            const swimlane: Swimlane = {
                card: card_to_move,
                children_cards: [],
                is_loading_children_cards: false,
            };
            const payload: MoveCardsPayload = { swimlane, column, card: card_to_move };

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.moveCardToCell(context, payload);

            expect(tlpPatchMock).toHaveBeenCalledWith("/api/v1/taskboard_cells/86/column/42", {
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ add: card_to_move.id }),
            });

            expect(context.dispatch).toHaveBeenCalledWith("refreshCardAndParent", {
                swimlane,
                card: card_to_move,
            });
            expect(context.commit).toHaveBeenCalledWith("moveCardToColumn", payload);
        });

        it("When the payload has a position, it will add it to the REST payload", async () => {
            const card_to_move = {
                id: 102,
                tracker_id: 7,
                mapped_list_value: { id: 49 },
                has_children: true,
            } as Card;
            const swimlane = { card: { id: 86 }, children_cards: [card_to_move] } as Swimlane;
            const position: CardPosition = {
                ids: [card_to_move.id],
                direction: BEFORE,
                compared_to: 100,
            };
            const payload: MoveCardsPayload = { swimlane, column, card: card_to_move, position };

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.moveCardToCell(context, payload);

            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/taskboard_cells/86/column/42`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    add: card_to_move.id,
                    order: {
                        ids: [102],
                        direction: "before",
                        compared_to: 100,
                    },
                }),
            });

            expect(context.commit).toHaveBeenCalledWith("moveCardToColumn", payload);
        });

        it("A modal opens on error", async () => {
            const card_to_move = {
                id: 102,
                tracker_id: 7,
                mapped_list_value: { id: 49 },
                has_children: true,
            } as Card;
            const swimlane = { card: { id: 86 }, children_cards: [card_to_move] } as Swimlane;
            const payload: MoveCardsPayload = { swimlane, column, card: card_to_move };

            const error = new Error();

            const tlpPatchMock = jest.spyOn(tlp_fetch, "patch");
            tlpPatchMock.mockRejectedValue(error);

            await actions.moveCardToCell(context, payload);

            expect(context.dispatch).toHaveBeenCalledWith("error/handleModalError", error, {
                root: true,
            });
        });
    });
});
