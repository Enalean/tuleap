/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { DraggedCard, State } from "../store/type";
import { LEFT, RIGHT, DOWN, UP } from "../type";
import {
    getContext,
    getDraggedCard,
    getTargetCell,
    focusDraggedCard,
    getNextSiblingAfterMove,
} from "./keyboard-drop";

describe(`keyboard-drop helper`, () => {
    let doc: Document;

    let first_cell: HTMLElement;
    let second_cell: HTMLElement;
    let third_cell: HTMLElement;
    let first_card: HTMLElement;
    let second_card: HTMLElement;
    let third_card: HTMLElement;

    const no_card_dragged_state = { card_being_dragged: null } as State;
    const card_dragged_state = { card_being_dragged: { card_id: 1 } as DraggedCard } as State;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocument(doc);
    });

    describe("getContext", () => {
        it("returns null if no card is being dragged", () => {
            const context = getContext(doc, no_card_dragged_state, RIGHT);

            expect(context).toBeNull();
        });

        it("returns null if there is no target cell", () => {
            const context = getContext(doc, card_dragged_state, LEFT);

            expect(context).toBeNull();
        });

        it("throws an error if card is not in a cell", () => {
            delete first_cell.dataset.navigation;

            expect(() => {
                getContext(doc, card_dragged_state, LEFT);
            }).toThrow();
        });

        it("returns a context object containing the dropped card, the source and target dropzone", () => {
            const context = getContext(doc, card_dragged_state, RIGHT);

            expect(context).toEqual({
                dropped_element: first_card,
                source_dropzone: first_cell,
                target_dropzone: second_cell,
                next_sibling: null,
            });
        });
    });

    describe("getTargetCell", () => {
        it("returns null if there is no target cell", () => {
            const target_cell = getTargetCell(third_cell, RIGHT);

            expect(target_cell).toBeNull();
        });

        it("returns null if target cell is not a container", () => {
            third_cell.removeAttribute("data-is-container");
            const target_cell = getTargetCell(third_cell, RIGHT);

            expect(target_cell).toBeNull();
        });

        it("returns the next cell if direction is RIGHT", () => {
            const target_cell = getTargetCell(second_cell, RIGHT);

            expect(target_cell).toBe(third_cell);
        });

        it("returns the previous cell if direction is LEFT", () => {
            const target_cell = getTargetCell(second_cell, LEFT);

            expect(target_cell).toBe(first_cell);
        });

        it("returns same cell if direction is UP or DOWN", () => {
            const target_cell = getTargetCell(second_cell, UP);

            expect(target_cell).toBe(second_cell);
        });
    });

    describe("getNextSiblingAfterMove", () => {
        it("returns null if direction is LEFT or RIGHT", () => {
            const next_sibling = getNextSiblingAfterMove(first_card, first_cell, LEFT);

            expect(next_sibling).toBeNull();
        });

        describe("direction is UP", () => {
            it("returns the card's previous sibling", () => {
                const next_sibling = getNextSiblingAfterMove(second_card, first_cell, UP);

                expect(next_sibling).toBe(first_card);
            });

            it("returns null if card is the first in cell", () => {
                const next_sibling = getNextSiblingAfterMove(first_card, first_cell, UP);

                expect(next_sibling).toBeNull();
            });
        });

        describe("direction is DOWN", () => {
            it("returns the card's after next sibling", () => {
                const next_sibling = getNextSiblingAfterMove(first_card, first_cell, DOWN);

                expect(next_sibling).toBe(third_card);
            });

            it("returns null if card is the before last in cell", () => {
                const next_sibling = getNextSiblingAfterMove(second_card, first_cell, DOWN);

                expect(next_sibling).toBeNull();
            });

            it("returns the first card in cell if card is the last in cell", () => {
                const next_sibling = getNextSiblingAfterMove(third_card, first_cell, DOWN);

                expect(next_sibling).toBe(first_card);
            });

            it("throws if first element in cell is not a card", () => {
                delete first_card.dataset.navigation;

                expect(() => {
                    getNextSiblingAfterMove(third_card, first_cell, DOWN);
                }).toThrow();
            });
        });
    });

    describe("getDraggedCard", () => {
        it("returns null if no card is being dragged", () => {
            const dragged_card = getDraggedCard(doc, no_card_dragged_state);

            expect(dragged_card).toBeNull();
        });

        it("returns the card being dragged", () => {
            const dragged_card = getDraggedCard(doc, card_dragged_state);

            expect(dragged_card).toBe(first_card);
        });
    });

    describe("replaceFocusAfterCardMove", () => {
        beforeEach(() => {
            document.body.innerHTML = "";
            setupDocument(document);
        });

        it("focuses the dragged card", () => {
            first_card.focus();
            focusDraggedCard(document, card_dragged_state);

            expect(document.activeElement).toEqual(first_card);
        });
    });

    function setupDocument(doc: Document): void {
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
                <div data-is-container="true" data-navigation='cell' data-test="first-cell">
                    <div data-card-id="1" tabindex="0" data-navigation='card' data-test="first-card"></div>
                    <div data-card-id="2" tabindex="0" data-navigation='card' data-test="second-card"></div>
                    <div data-card-id="3" tabindex="0" data-navigation='card' data-test="third-card"></div>
                </div>
                <div data-is-container="true" data-navigation='cell' data-test="second-cell"></div>
                <div data-is-container="true" data-navigation='cell' data-test="third-cell"></div>
            `,
        );

        const first_cell_element = doc.querySelector("[data-test='first-cell']");
        const second_cell_element = doc.querySelector("[data-test='second-cell']");
        const third_cell_element = doc.querySelector("[data-test='third-cell']");
        const first_card_element = doc.querySelector("[data-test='first-card']");
        const second_card_element = doc.querySelector("[data-test='second-card']");
        const third_card_element = doc.querySelector("[data-test='third-card']");

        if (
            !(first_cell_element instanceof HTMLElement) ||
            !(second_cell_element instanceof HTMLElement) ||
            !(third_cell_element instanceof HTMLElement) ||
            !(first_card_element instanceof HTMLElement) ||
            !(second_card_element instanceof HTMLElement) ||
            !(third_card_element instanceof HTMLElement)
        ) {
            throw new Error("Bad setup");
        }

        first_cell = first_cell_element;
        second_cell = second_cell_element;
        third_cell = third_cell_element;
        first_card = first_card_element;
        second_card = second_card_element;
        third_card = third_card_element;
    }
});
