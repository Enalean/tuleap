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
import { injectDefaultPropertiesInCard } from "./card-default";

describe("injectDefaultPropertiesInCard", () => {
    it("Sets is_in_edit_mode to false", () => {
        const card: Card = {} as Card;
        injectDefaultPropertiesInCard(card);
        expect(card.is_in_edit_mode).toBe(false);
    });

    it("Sets is_being_saved to false", () => {
        const card: Card = {} as Card;
        injectDefaultPropertiesInCard(card);
        expect(card.is_being_saved).toBe(false);
    });

    it("Sets is_just_saved to false", () => {
        const card: Card = {} as Card;
        injectDefaultPropertiesInCard(card);
        expect(card.is_just_saved).toBe(false);
    });

    it("Does not change the remaining effort if it is null", () => {
        const card: Card = { remaining_effort: null } as Card;
        injectDefaultPropertiesInCard(card);
        expect(card.remaining_effort).toBeNull();
    });

    it("Set is_being_saved for remaining effort to false", () => {
        const card: Card = { remaining_effort: { value: 3 } } as Card;
        injectDefaultPropertiesInCard(card);

        if (!card.remaining_effort) {
            throw new Error("Expected the card to have a remaining effort");
        }
        expect(card.remaining_effort.is_being_saved).toBe(false);
    });

    it("Set is_in_edit_mode for remaining effort to false", () => {
        const card: Card = { remaining_effort: { value: 3 } } as Card;
        injectDefaultPropertiesInCard(card);

        if (!card.remaining_effort) {
            throw new Error("Expected the card to have a remaining effort");
        }
        expect(card.remaining_effort.is_in_edit_mode).toBe(false);
    });
});
