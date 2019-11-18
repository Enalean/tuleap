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

import { RootState } from "./type";
import { Card, ColumnDefinition, Swimlane } from "../type";
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
                columns: [column_to_find]
            },
            swimlane: {
                swimlanes: [swimlane_to_find]
            }
        } as RootState;
    });

    it("shoud return the column and the swimlane referenced by the cell", () => {
        const target_cell = getCellElement(
            swimlane_to_find.card.id.toString(),
            column_to_find.id.toString()
        );

        const { swimlane, column } = getters.column_and_swimlane_of_cell(root_state)(target_cell);

        if (!swimlane || !column) {
            throw new Error("swimlane or column have not been found");
        }

        expect(swimlane.card.id).toEqual(100);
        expect(column.label).toEqual("Todo");
    });

    it("should return an undefined swimlane or column if one or the other have not been found", () => {
        const target_cell = getCellElement("300", "200");

        const { swimlane, column } = getters.column_and_swimlane_of_cell(root_state)(target_cell);

        expect(swimlane).toBeUndefined();
        expect(column).toBeUndefined();
    });
});

function getCellElement(swimlane_id: string, column_id: string): HTMLElement {
    const target_cell = createElement();

    target_cell.setAttribute("data-swimlane-id", swimlane_id);
    target_cell.setAttribute("data-column-id", column_id);

    return target_cell;
}
