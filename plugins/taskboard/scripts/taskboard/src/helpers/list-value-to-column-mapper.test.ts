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
import { getColumnOfCard } from "./list-value-to-column-mapper";
import { Card, ColumnDefinition } from "../type";

describe(`list-value-to-column-mapper`, () => {
    describe("getColumnOfCard", () => {
        it(`Given columns and a card,
            it will return the first column that has a mapping
            with the same tracker id that accepts this card's mapped_list_value`, () => {
            const first_column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 7546 }] },
                    { tracker_id: 46, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;
            const second_column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 5398 }] },
                    { tracker_id: 47, accepts: [{ id: 9857 }] },
                ],
            } as ColumnDefinition;
            const card = { tracker_id: 45, mapped_list_value: { id: 5398 } } as Card;

            const result = getColumnOfCard([first_column, second_column], card);

            expect(result).toBe(second_column);
        });

        it(`when there are two columns that would accept the card,
            only the first one will be returned`, () => {
            const first_column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 7546 }] },
                    { tracker_id: 46, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;
            const second_column = {
                mappings: [{ tracker_id: 45, accepts: [{ id: 7546 }] }],
            } as ColumnDefinition;
            const card = { tracker_id: 45, mapped_list_value: { id: 7546 } } as Card;

            const result = getColumnOfCard([first_column, second_column], card);

            expect(result).toBe(first_column);
        });

        it(`when the column accepts multiple values, then it will return the column`, () => {
            const column = {
                mappings: [{ tracker_id: 45, accepts: [{ id: 4366 }, { id: 7546 }] }],
            } as ColumnDefinition;
            const card = { tracker_id: 45, mapped_list_value: { id: 7546 } } as Card;

            const result = getColumnOfCard([column], card);

            expect(result).toBe(column);
        });

        it(`when there is no mapping for the card's tracker id, it will return undefined`, () => {
            const column = {
                mappings: [{ tracker_id: 51, accepts: [{ id: 9857 }] }],
            } as ColumnDefinition;
            const card = { tracker_id: 45, mapped_list_value: { id: 9857 } } as Card;

            expect(getColumnOfCard([column], card)).toBeUndefined();
        });

        it(`when there are no columns, it will return undefined`, () => {
            const card = { tracker_id: 45, mapped_list_value: { id: 7546 } } as Card;

            expect(getColumnOfCard([], card)).toBeUndefined();
        });

        it(`when the card has no mapped_list_value, it will return undefined`, () => {
            const card = { tracker_id: 45, mapped_list_value: null } as Card;
            const column = {
                mappings: [{ tracker_id: 45, accepts: [{ id: 7546 }] }],
            } as ColumnDefinition;
            const columns = [column];

            expect(getColumnOfCard(columns, card)).toBeUndefined();
        });
    });
});
