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

import {
    hasCardBeenDroppedInTheSameCell,
    getCardFromSwimlane,
    getColumnAndSwimlaneFromCell
} from "./html-to-item";
import { Card, ColumnDefinition, Swimlane } from "../type";

describe("html-to-item helper", () => {
    describe("hasCardBeenDroppedInTheSameCell", () => {
        it("returns true if the target cell is also the source cell", (): void => {
            const { target_cell, source_cell } = getSourceAndTargetCellsAndCard();

            target_cell.setAttribute("data-swimlane-id", "100");
            target_cell.setAttribute("data-column-id", "15");

            source_cell.setAttribute("data-swimlane-id", "100");
            source_cell.setAttribute("data-column-id", "15");

            expect(hasCardBeenDroppedInTheSameCell(target_cell, source_cell)).toBe(true);
        });

        it("returns false if the target cell and the source cell are two different cells of the same swimlane", () => {
            const { target_cell, source_cell } = getSourceAndTargetCellsAndCard();

            target_cell.setAttribute("data-swimlane-id", "100");
            target_cell.setAttribute("data-column-id", "15");

            source_cell.setAttribute("data-swimlane-id", "100");
            source_cell.setAttribute("data-column-id", "16");

            expect(hasCardBeenDroppedInTheSameCell(target_cell, source_cell)).toBe(false);
        });

        it("returns false if the target cell and the source cell are two different cells from different swimlanes", () => {
            const { target_cell, source_cell } = getSourceAndTargetCellsAndCard();

            target_cell.setAttribute("data-swimlane-id", "100");
            target_cell.setAttribute("data-column-id", "15");

            source_cell.setAttribute("data-swimlane-id", "101");
            source_cell.setAttribute("data-column-id", "16");

            expect(hasCardBeenDroppedInTheSameCell(target_cell, source_cell)).toBe(false);
        });

        it("returns false if the target cell and the source cell are in the same column but from different swimlanes", () => {
            const { target_cell, source_cell } = getSourceAndTargetCellsAndCard();

            target_cell.setAttribute("data-swimlane-id", "100");
            target_cell.setAttribute("data-column-id", "15");

            source_cell.setAttribute("data-swimlane-id", "101");
            source_cell.setAttribute("data-column-id", "15");

            expect(hasCardBeenDroppedInTheSameCell(target_cell, source_cell)).toBe(false);
        });
    });

    describe("getCardFromSwimlane", () => {
        it("returns the card given a card_element and a swimlane", () => {
            const { card_element } = getSourceAndTargetCellsAndCard();

            card_element.setAttribute("data-card-id", "150");

            const swimlane = {
                card: { id: 100 } as Card,
                children_cards: [
                    { id: 145 } as Card,
                    { id: 146 } as Card,
                    { id: 150, label: "I am the card you look for" } as Card
                ]
            } as Swimlane;

            const card = getCardFromSwimlane(swimlane, card_element);

            if (!card) {
                throw new Error("Card has not been found");
            }

            expect(card.id).toEqual(150);
            expect(card.label).toEqual("I am the card you look for");
        });

        it("returns null if there is not card element", () => {
            let card_element;
            const swimlane = {
                card: { id: 100 } as Card,
                children_cards: [
                    { id: 145 } as Card,
                    { id: 146 } as Card,
                    { id: 150, label: "I am the card you look for" } as Card
                ]
            } as Swimlane;

            const card = getCardFromSwimlane(swimlane, card_element); // card_element is undefined

            expect(card).toBeNull();
        });

        it("returns null if the card has not been found in the swimlane", () => {
            const { card_element } = getSourceAndTargetCellsAndCard();

            card_element.setAttribute("data-card-id", "666");

            const swimlane = {
                card: { id: 100 } as Card,
                children_cards: [
                    { id: 145 } as Card,
                    { id: 146 } as Card,
                    { id: 150, label: "I am the card you look for" } as Card
                ]
            } as Swimlane;

            const card = getCardFromSwimlane(swimlane, card_element);

            expect(card).toBeNull();
        });
    });

    describe("getColumnAndSwimlaneFromCell", () => {
        it("shoud return the column and the swimlane referenced by the cell", () => {
            const { target_cell } = getSourceAndTargetCellsAndCard();

            target_cell.setAttribute("data-swimlane-id", "100");
            target_cell.setAttribute("data-column-id", "15");

            const column_to_find = { id: 15, label: "Todo" } as ColumnDefinition;
            const swimlane_to_find = {
                card: { id: 100 } as Card
            } as Swimlane;

            const swimlanes = [swimlane_to_find];
            const columns = [column_to_find];

            const { swimlane, column } = getColumnAndSwimlaneFromCell(
                swimlanes,
                columns,
                target_cell
            );

            if (!swimlane || !column) {
                throw new Error("swimlane or column have not been found");
            }

            expect(swimlane.card.id).toEqual(100);
            expect(column.label).toEqual("Todo");
        });

        it("should return an undefined swimlane or column if one or the other have not been found", () => {
            const { target_cell } = getSourceAndTargetCellsAndCard();

            target_cell.setAttribute("data-swimlane-id", "100");
            target_cell.setAttribute("data-column-id", "15");

            const swimlanes: Swimlane[] = [];
            const columns: ColumnDefinition[] = [];

            const { swimlane, column } = getColumnAndSwimlaneFromCell(
                swimlanes,
                columns,
                target_cell
            );

            expect(swimlane).toBeUndefined();
            expect(column).toBeUndefined();
        });
    });
});

function getSourceAndTargetCellsAndCard(): {
    target_cell: HTMLElement;
    source_cell: HTMLElement;
    card_element: HTMLElement;
} {
    const local_document = document.implementation.createHTMLDocument();

    const target_cell = local_document.createElement("div");
    const source_cell = local_document.createElement("div");
    const card_element = local_document.createElement("div");

    return {
        target_cell,
        source_cell,
        card_element
    };
}
