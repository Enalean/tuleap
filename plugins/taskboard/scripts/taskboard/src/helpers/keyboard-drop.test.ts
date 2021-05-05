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
import { LEFT, RIGHT } from "../type";
import { getContext, getDraggedCard, getTargetCell, focusDraggedCard } from "./keyboard-drop";

describe(`keyboard-drop helper`, () => {
    let doc: Document;

    let first_cell: HTMLElement;
    let second_cell: HTMLElement;
    let third_cell: HTMLElement;
    let card: HTMLElement;

    const no_card_dragged_state = { card_being_dragged: null } as State;
    const card_dragged_state = { card_being_dragged: { card_id: 1 } as DraggedCard } as State;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocument(doc);
    });

    describe("getContext", () => {
        it("returns null if no card is being dragged", () => {
            const context = getContext(doc, no_card_dragged_state, RIGHT);

            expect(context).toBe(null);
        });

        it("returns null if there is no target cell", () => {
            const context = getContext(doc, card_dragged_state, LEFT);

            expect(context).toBe(null);
        });

        it("returns a context object containing the dropped card, the source and target dropzone", () => {
            const context = getContext(doc, card_dragged_state, RIGHT);

            expect(context).toEqual({
                dropped_element: card,
                source_dropzone: first_cell,
                target_dropzone: second_cell,
                next_sibling: null,
            });
        });
    });

    describe("getTargetCell", () => {
        it("returns null if there is no target cell", () => {
            const target_cell = getTargetCell(third_cell, RIGHT);

            expect(target_cell).toBe(null);
        });

        it("returns null if target cell is not a container", () => {
            third_cell.removeAttribute("data-is-container");
            const target_cell = getTargetCell(third_cell, RIGHT);

            expect(target_cell).toBe(null);
        });

        it("returns the next cell if direction is RIGHT", () => {
            const target_cell = getTargetCell(second_cell, RIGHT);

            expect(target_cell).toBe(third_cell);
        });

        it("returns the previous cell if direction is LEFT", () => {
            const target_cell = getTargetCell(second_cell, LEFT);

            expect(target_cell).toBe(first_cell);
        });
    });

    describe("getDraggedCard", () => {
        it("returns null if no card is being dragged", () => {
            const dragged_card = getDraggedCard(doc, no_card_dragged_state);

            expect(dragged_card).toBe(null);
        });

        it("returns the card being dragged", () => {
            const dragged_card = getDraggedCard(doc, card_dragged_state);

            expect(dragged_card).toBe(card);
        });
    });

    describe("replaceFocusAfterCardMove", () => {
        beforeEach(() => {
            document.body.innerHTML = "";
            setupDocument(document);
        });

        it("focuses the dragged card", () => {
            card.focus();
            focusDraggedCard(document, card_dragged_state);

            expect(document.activeElement).toEqual(card);
        });
    });

    function setupDocument(doc: Document): void {
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
                <div data-is-container="true" data-navigation='cell' data-test="first-cell">
                    <div data-card-id="1" tabindex="0" data-test="card"></div>
                </div>
                <div data-is-container="true" data-navigation='cell' data-test="second-cell"></div>
                <div data-is-container="true" data-navigation='cell' data-test="third-cell"></div>
            `
        );

        const maybe_first_cell = doc.querySelector("[data-test='first-cell']");
        const maybe_second_cell = doc.querySelector("[data-test='second-cell']");
        const maybe_third_cell = doc.querySelector("[data-test='third-cell']");
        const maybe_card = doc.querySelector("[data-test='card']");

        if (
            !(maybe_first_cell instanceof HTMLElement) ||
            !(maybe_second_cell instanceof HTMLElement) ||
            !(maybe_third_cell instanceof HTMLElement) ||
            !(maybe_card instanceof HTMLElement)
        ) {
            throw new Error("Bad setup");
        }

        first_cell = maybe_first_cell;
        second_cell = maybe_second_cell;
        third_cell = maybe_third_cell;
        card = maybe_card;
    }
});
