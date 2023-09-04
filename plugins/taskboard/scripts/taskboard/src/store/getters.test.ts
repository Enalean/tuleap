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

import type { RootState } from "./type";
import type { Card, ColumnDefinition, Swimlane } from "../type";
import * as getters from "./getters";
import { createElement } from "../helpers/jest/create-dom-element";

describe("column_and_swimlane_of_cell", () => {
    let root_state: RootState;
    let swimlane_to_find: Swimlane;
    let column_to_find: ColumnDefinition;

    beforeEach(() => {
        swimlane_to_find = { card: { id: 100 } as Card } as Swimlane;
        column_to_find = { id: 15, label: "Todo" } as ColumnDefinition;

        root_state = {
            column: {
                columns: [column_to_find],
            },
            swimlane: {
                swimlanes: [swimlane_to_find],
            },
            trackers: [
                { id: 1, add_in_place: { child_tracker_id: 1533 } },
                { id: 2, add_in_place: null },
            ],
        } as RootState;
    });

    it("shoud return the column and the swimlane referenced by the cell", () => {
        const target_cell = getCellElement(
            swimlane_to_find.card.id.toString(),
            column_to_find.id.toString(),
        );

        const { swimlane, column } = getters.column_and_swimlane_of_cell(root_state)(target_cell);

        if (!swimlane || !column) {
            throw new Error("swimlane or column have not been found");
        }

        expect(swimlane.card.id).toBe(100);
        expect(column.label).toBe("Todo");
    });

    it("should return an undefined swimlane or column if one or the other have not been found", () => {
        const target_cell = getCellElement("300", "200");

        const { swimlane, column } = getters.column_and_swimlane_of_cell(root_state)(target_cell);

        expect(swimlane).toBeUndefined();
        expect(column).toBeUndefined();
    });

    describe("tracker_of_card", () => {
        it("Given a card, it returns its tracker", () => {
            const card = { id: 100, tracker_id: 1 } as Card;
            const expected_tracker = root_state.trackers[0];

            expect(getters.tracker_of_card(root_state)(card)).toEqual(expected_tracker);
        });

        it("Throws an error when the tracker is not found", () => {
            const card = { id: 100, tracker_id: 3 } as Card;

            expect(() => getters.tracker_of_card(root_state)(card)).toThrow("not been found");
        });
    });

    describe("can_add_in_place", () => {
        it("returns true when the tracker of the given swimlane supports the add-in-place feature", () => {
            const swimlane = { card: { id: 111, tracker_id: 1 } } as Swimlane;

            expect(getters.can_add_in_place(root_state)(swimlane)).toBe(true);
        });

        it("returns false when the tracker of the given swimlane does not support the add-in-place feature", () => {
            const swimlane = { card: { id: 111, tracker_id: 2 } } as Swimlane;

            expect(getters.can_add_in_place(root_state)(swimlane)).toBe(false);
        });
    });
});

function getCellElement(swimlane_id: string, column_id: string): HTMLElement {
    const target_cell = createElement();

    target_cell.setAttribute("data-swimlane-id", swimlane_id);
    target_cell.setAttribute("data-column-id", column_id);

    return target_cell;
}
