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

import type { Card, ColumnDefinition, Mapping } from "../type";

export function getColumnOfCard(
    columns: ColumnDefinition[],
    card: Card,
): ColumnDefinition | undefined {
    if (card.mapped_list_value === null) {
        return undefined;
    }
    return columns.find((column) => isStatusAcceptedByColumn(card, column));
}

export function isStatusAcceptedByColumn(card: Card, column: ColumnDefinition): boolean {
    return column.mappings.some((mapping) => isStatusPartOfMapping(card, mapping));
}

function isStatusPartOfMapping(card: Card, mapping: Mapping): boolean {
    return (
        mapping.tracker_id === card.tracker_id &&
        mapping.accepts.some(
            (list_value) =>
                card.mapped_list_value !== null && list_value.id === card.mapped_list_value.id,
        )
    );
}
