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

import { Card, ColumnDefinition, Swimlane } from "../type";
import { getCardsInColumn } from "./column-cards";

describe("column-cards", () => {
    describe("getCardsInColumn", () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card
            ],
            is_loading_children_cards: false
        } as Swimlane;

        const todo: ColumnDefinition = {
            id: 2,
            label: "To do",
            mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }]
        } as ColumnDefinition;

        const done: ColumnDefinition = {
            id: 3,
            label: "Done",
            mappings: [{ tracker_id: 7, accepts: [{ id: 50 }] }]
        } as ColumnDefinition;

        const taskboard_columns = [todo, done];
        const current_column = todo;

        it("Should return the cards of the column", () => {
            expect(getCardsInColumn(swimlane, current_column, taskboard_columns)).toEqual([
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } },
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } }
            ]);
        });
    });
});
