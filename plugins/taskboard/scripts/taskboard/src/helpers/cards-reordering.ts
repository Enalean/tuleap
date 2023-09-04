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

import type { Card, CardPosition, Direction } from "../type";
import { AFTER, BEFORE } from "../type";

export function getCardPosition(
    card: Card,
    sibling: Card | null,
    cards_in_cell: Card[],
): CardPosition {
    const ids = [card.id];

    if (!sibling) {
        const direction = AFTER;
        const last_card_in_column = cards_in_cell[cards_in_cell.length - 1];
        const compared_to = last_card_in_column.id;

        return formatOrdering(ids, direction, compared_to);
    }

    const { direction, compared_to } = getCardToCompareWith(cards_in_cell, sibling);

    return formatOrdering(ids, direction, compared_to);
}

function getCardToCompareWith(
    cards_in_cell: Card[],
    sibling: Card,
): { direction: Direction; compared_to: number } {
    const index = cards_in_cell.findIndex((column_card) => column_card.id === sibling.id);

    if (index === 0) {
        return {
            direction: BEFORE,
            compared_to: cards_in_cell[0].id,
        };
    }

    return {
        direction: AFTER,
        compared_to: cards_in_cell[index - 1].id,
    };
}

function formatOrdering(ids: number[], direction: Direction, compared_to: number): CardPosition {
    return {
        ids,
        direction,
        compared_to,
    };
}
