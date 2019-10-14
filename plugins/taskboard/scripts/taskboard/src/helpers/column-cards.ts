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

import { ColumnDefinition, Swimlane, Card } from "../type";
import { getColumnOfCard } from "./list-value-to-column-mapper";

export function getCardsInColumn(
    swimlane: Swimlane,
    current_column: ColumnDefinition,
    taskboard_columns: ColumnDefinition[]
): Card[] {
    return swimlane.children_cards.filter(card => {
        const column_of_card = getColumnOfCard(taskboard_columns, card);

        if (!column_of_card) {
            return false;
        }

        return column_of_card.id === current_column.id;
    });
}
