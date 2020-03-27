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

import { SwimlaneState } from "./type";
import { Swimlane, Card } from "../../type";
import * as helpers from "./swimlane-helpers";

describe(`swimlane-helpers`, () => {
    let state: SwimlaneState, second_swimlane: Swimlane;
    beforeEach(() => {
        const first_swimlane = { card: { id: 16 } } as Swimlane;
        second_swimlane = { card: { id: 24 } } as Swimlane;
        const third_swimlane = { card: { id: 35 } } as Swimlane;
        state = {
            swimlanes: [first_swimlane, second_swimlane, third_swimlane],
        } as SwimlaneState;
    });

    describe(`findSwimlane`, () => {
        it(`Given a swimlane,
            it will find the swimlane stored in the state with
            the same top-level card id`, () => {
            const needle = { card: { id: 24 } } as Swimlane;
            const result = helpers.findSwimlane(state, needle);
            expect(result).toBe(second_swimlane);
        });

        it(`Given an unknown swimlane,
            it will throw an error`, () => {
            const unknown_swimlane = { card: { id: 999 } } as Swimlane;
            expect(() => helpers.findSwimlane(state, unknown_swimlane)).toThrow();
        });
    });

    describe(`findSwimlaneIndex`, () => {
        it(`Given a swimlane, it will find its index in the state`, () => {
            const needle = { card: { id: 24 } } as Swimlane;
            const result = helpers.findSwimlaneIndex(state, needle);
            expect(result).toBe(1);
        });

        it(`Given an unknown swimlane, it will return -1`, () => {
            const unknown_swimlane = { card: { id: 999 } } as Swimlane;
            const result = helpers.findSwimlaneIndex(state, unknown_swimlane);
            expect(result).toBe(-1);
        });
    });

    describe(`replaceSwimlane`, () => {
        it(`Given a swimlane,
            it will find it in the state and replace it with the parameter`, () => {
            const replacement = ({
                card: { id: 24 },
                children_cards: [{ card: { id: 473 } }],
            } as unknown) as Swimlane;
            helpers.replaceSwimlane(state, replacement);

            const replacement_index = helpers.findSwimlaneIndex(state, replacement);
            expect(replacement_index).toBe(1);
            const replaced = state.swimlanes[replacement_index];
            expect(replaced).toBe(replacement);
        });

        it(`Given an unknown swimlane, it won't touch the state`, () => {
            const unknown_swimlane = { card: { id: 999 } } as Swimlane;

            helpers.replaceSwimlane(state, unknown_swimlane);

            const unknown_index = helpers.findSwimlaneIndex(state, unknown_swimlane);
            expect(unknown_index).toBe(-1);
        });
    });

    describe("findCard", () => {
        it(`Given a card,
            when the card is a solo card,
            it will return the card with matching id from the state`, () => {
            const solo_card = { id: 24, label: "Parameter card", has_children: false } as Card;
            const card_from_state = {
                id: 24,
                label: "Card from state",
                has_children: false,
            } as Card;
            const swimlane = {
                card: card_from_state,
                children_cards: [],
                is_loading_children_cards: false,
            } as Swimlane;
            const state = {
                swimlanes: [
                    { card: { id: 99, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;

            const result = helpers.findCard(state, solo_card);
            expect(result).toBe(card_from_state);
        });

        it(`Given a card,
            when the card is a parent card,
            it will return the card with matching id from the state`, () => {
            const parent_card = { id: 1, label: "Parameter card", has_children: true } as Card;
            const card_from_state = { id: 1, label: "Card from state", has_children: true };
            const swimlane = {
                card: card_from_state,
                children_cards: [{ id: 19, label: "Unrelated child" }],
            };
            const state = {
                swimlanes: [
                    { card: { id: 99, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;

            const result = helpers.findCard(state, parent_card);
            expect(result).toBe(card_from_state);
        });

        it(`Given a card,
            when the card is a child card,
            it will return the card with matching id from the state`, () => {
            const child_card = { id: 10, label: "Parameter card" } as Card;
            const card_from_state = { id: 10, label: "Card from state" };
            const swimlane = {
                card: { id: 1, has_children: true },
                children_cards: [{ id: 19, label: "Unrelated child" }, card_from_state],
            };
            const state = {
                swimlanes: [
                    { card: { id: 99, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;

            const result = helpers.findCard(state, child_card);
            expect(result).toBe(card_from_state);
        });

        it(`when no card with the same id can be found in the state,
            it will throw an error to let the developper know there is something wrong
            in the code`, () => {
            const unknown_card = { id: 10, label: "Parameter card" } as Card;
            const state = {
                swimlanes: [
                    {
                        card: { id: 99, label: "Unrelated swimlane" } as Card,
                        children_cards: [] as Card[],
                        is_loading_children_cards: false,
                    },
                ],
                is_loading_swimlanes: false,
            } as SwimlaneState;
            expect(() => helpers.findCard(state, unknown_card)).toThrow();
        });
    });

    describe(`isSoloCard`, () => {
        it(`Given a swimlane with children, it will return false`, () => {
            const swimlane: Swimlane = {
                card: { id: 13, has_children: true },
                children_cards: [{ id: 104 }, { id: 125 }],
                is_loading_children_cards: false,
            } as Swimlane;

            expect(helpers.isSoloCard(swimlane)).toBe(false);
        });

        it(`Given a swimlane that has children but the user can't see them,
            it will return true`, () => {
            const swimlane: Swimlane = {
                card: { id: 45, has_children: true } as Card,
                children_cards: [],
                is_loading_children_cards: false,
            } as Swimlane;

            expect(helpers.isSoloCard(swimlane)).toBe(false);
        });

        it(`Given a swimlane without children, it will return true`, () => {
            const swimlane: Swimlane = {
                card: { id: 14, has_children: false } as Card,
                children_cards: [],
                is_loading_children_cards: false,
            };

            expect(helpers.isSoloCard(swimlane)).toBe(true);
        });
    });
});
