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

import { Card } from "../type";
import { getCardPosition } from "./cards-reordering";

describe("cards-reordering", () => {
    let card: Card;
    let cards_in_cell: Card[] = [];

    beforeEach(() => {
        card = { id: 105 } as Card;

        cards_in_cell = [
            { id: 100 } as Card,
            { id: 101 } as Card,
            card,
            { id: 102 } as Card,
            { id: 103 } as Card,
        ];
    });

    describe("getCardPosition", () => {
        it("If card has no sibling in the dropped cell, then it means it is placed at the end of the list", () => {
            expect(getCardPosition(card, null, cards_in_cell)).toEqual({
                ids: [card.id],
                direction: "after",
                compared_to: 103,
            });
        });
        it("If card has been dropped at the first position, then it should be placed before the second item of the list", () => {
            expect(getCardPosition(card, { id: 100 } as Card, cards_in_cell)).toEqual({
                ids: [card.id],
                direction: "before",
                compared_to: 100,
            });
        });
        it("If the card has been dropped between the second and the third position, then it should be placed after the second item of the list", () => {
            expect(getCardPosition(card, { id: 103 } as Card, cards_in_cell)).toEqual({
                ids: [card.id],
                direction: "after",
                compared_to: 102,
            });
        });
    });
});
