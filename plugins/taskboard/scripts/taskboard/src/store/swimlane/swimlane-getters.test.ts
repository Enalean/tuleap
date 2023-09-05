/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import * as getters from "./swimlane-getters";
import type { SwimlaneState } from "./type";
import type { Card, ColumnDefinition, Swimlane } from "../../type";
import type { RootState } from "../type";

jest.mock("tlp");

describe("Swimlane state getters", () => {
    describe("is_loading_cards", () => {
        it("returns true if swimlanes are still loading", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: true,
            } as SwimlaneState;

            expect(getters.is_loading_cards(state)).toBe(true);
        });

        it("returns true if swimlanes are loaded but at least one of swimlanes is still loading its children", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: false,
                swimlanes: [
                    { is_loading_children_cards: false } as Swimlane,
                    { is_loading_children_cards: true } as Swimlane,
                    { is_loading_children_cards: false } as Swimlane,
                ],
            } as SwimlaneState;

            expect(getters.is_loading_cards(state)).toBe(true);
        });

        it("returns false if swimlanes are loaded and their children are loaded", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: false,
                swimlanes: [
                    { is_loading_children_cards: false } as Swimlane,
                    { is_loading_children_cards: false } as Swimlane,
                    { is_loading_children_cards: false } as Swimlane,
                ],
            } as SwimlaneState;

            expect(getters.is_loading_cards(state)).toBe(false);
        });
    });

    describe("nb_cards_in_column", () => {
        let column: ColumnDefinition;
        beforeEach(() => {
            column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 7546 }] },
                    { tracker_id: 46, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;
        });

        it("returns 0 if no swimlanes", () => {
            const state: SwimlaneState = {
                swimlanes: [] as Swimlane[],
            } as SwimlaneState;

            expect(getters.nb_cards_in_column(state)(column)).toBe(0);
        });

        it("returns the sum of children in the given column and ignore their parent", () => {
            const state: SwimlaneState = {
                swimlanes: [
                    {
                        card: {
                            id: 1,
                            label: "parent 1 is in column 7546 but has children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: true,
                        },
                        children_cards: [
                            {
                                id: 2,
                                label: "children 2 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                            {
                                id: 3,
                                label: "children 3 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                        ],
                    },
                    {
                        card: {
                            id: 4,
                            label: "parent 4 is in column 7546 but has children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: true,
                        },
                        children_cards: [
                            {
                                id: 5,
                                label: "children 5 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                            {
                                id: 6,
                                label: "children 6 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                        ],
                    },
                ],
            } as SwimlaneState;

            expect(getters.nb_cards_in_column(state)(column)).toBe(4);
        });

        it("returns the sum of children in the given column and adds solo cards in given column", () => {
            const state: SwimlaneState = {
                swimlanes: [
                    {
                        card: {
                            id: 1,
                            label: "parent 1 is in column 7546 but has children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: true,
                        },
                        children_cards: [
                            {
                                id: 2,
                                label: "children 2 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                            {
                                id: 3,
                                label: "children 3 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                        ],
                    },
                    {
                        card: {
                            id: 4,
                            label: "parent 4 is in column 7546 and has no children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: false,
                        },
                    },
                    {
                        card: {
                            id: 5,
                            label: "parent 5 is NOT in column",
                            tracker_id: 45,
                            mapped_list_value: { id: 8000 },
                            has_children: false,
                        },
                    },
                ],
            } as SwimlaneState;

            expect(getters.nb_cards_in_column(state)(column)).toBe(3);
        });

        it("ignores children that are not in column", () => {
            const state: SwimlaneState = {
                swimlanes: [
                    {
                        card: {
                            id: 1,
                            label: "parent 1 is in column 7546 but has children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: true,
                        },
                        children_cards: [
                            {
                                id: 2,
                                label: "children 2 is NOT in column",
                                tracker_id: 45,
                                mapped_list_value: { id: 8000 },
                            },
                            {
                                id: 3,
                                label: "children 3 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 },
                            },
                        ],
                    },
                ],
            } as SwimlaneState;

            expect(getters.nb_cards_in_column(state)(column)).toBe(1);
        });
    });

    describe("cards_in_cell", () => {
        let swimlane_state: SwimlaneState;
        let root_state: RootState;
        let column_todo: ColumnDefinition;

        beforeEach(() => {
            column_todo = {
                id: 2,
                label: "To do",
                mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }],
            } as ColumnDefinition;

            swimlane_state = { swimlanes: [] as Swimlane[] } as SwimlaneState;

            root_state = {
                column: {
                    columns: [column_todo],
                },
            } as RootState;
        });

        it("Should return the cards of the column", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: true } as Card,
                children_cards: [
                    { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                    { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                    { id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card,
                ],
                is_loading_children_cards: false,
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.cards_in_cell(swimlane_state, [], root_state)(swimlane, column_todo),
            ).toEqual([
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } },
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } },
            ]);
        });

        it("Should return an empty array if it is a solo card swimlane", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: false } as Card,
                children_cards: [],
                is_loading_children_cards: false,
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.cards_in_cell(swimlane_state, [], root_state)(swimlane, column_todo),
            ).toEqual([]);
        });
    });

    describe("is_there_at_least_one_children_to_display", () => {
        let swimlane_state: SwimlaneState;
        let root_state: RootState;

        beforeEach(() => {
            const column_todo = {
                id: 2,
                label: "To do",
                mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }],
            } as ColumnDefinition;

            swimlane_state = { swimlanes: [] as Swimlane[] } as SwimlaneState;

            root_state = {
                column: {
                    columns: [column_todo],
                },
            } as RootState;
        });

        it("Has no children to display when the card is known to have no children", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: false } as Card,
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.is_there_at_least_one_children_to_display(
                    swimlane_state,
                    [],
                    root_state,
                )(swimlane),
            ).toBe(false);
        });

        it("Has no children to display when the card has no visible children in columns", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: true } as Card,
                children_cards: [{ id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card],
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.is_there_at_least_one_children_to_display(
                    swimlane_state,
                    [],
                    root_state,
                )(swimlane),
            ).toBe(false);
        });

        it("Has children to display when the card has at least one visible child in column", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: true } as Card,
                children_cards: [
                    { id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card,
                    { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                ],
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.is_there_at_least_one_children_to_display(
                    swimlane_state,
                    [],
                    root_state,
                )(swimlane),
            ).toBe(true);
        });
    });

    describe("has_at_least_one_card_in_edit_mode", () => {
        it("Returns false if there isn't any parent card in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                    {
                        card: { remaining_effort: { is_in_edit_mode: false } },
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                ],
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(false);
        });
        it("Returns true if there is a parent card with remaining effort in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                    {
                        card: { remaining_effort: { is_in_edit_mode: true } },
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                ],
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(true);
        });
        it("Returns true if there is a parent card in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                    {
                        card: { is_in_edit_mode: true },
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                ],
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(true);
        });
        it("Returns true if there is a children card in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card],
                    } as Swimlane,
                    {
                        card: {},
                        children_cards: [{} as Card, { is_in_edit_mode: true } as Card],
                    } as Swimlane,
                ],
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(true);
        });
    });

    describe("is_a_parent_card_in_edit_mode", () => {
        it("returns false if no parent card is in edit mode", () => {
            const state: SwimlaneState = {
                swimlanes: [
                    { card: { is_in_edit_mode: false } },
                    { card: { is_in_edit_mode: false } },
                    { card: { is_in_edit_mode: false } },
                ],
            } as SwimlaneState;

            expect(getters.is_a_parent_card_in_edit_mode(state)).toBe(false);
        });

        it("returns true if a parent card is in edit mode", () => {
            const state: SwimlaneState = {
                swimlanes: [
                    { card: { is_in_edit_mode: false } },
                    { card: { is_in_edit_mode: true } },
                    { card: { is_in_edit_mode: false } },
                ],
            } as SwimlaneState;

            expect(getters.is_a_parent_card_in_edit_mode(state)).toBe(true);
        });
    });
});
