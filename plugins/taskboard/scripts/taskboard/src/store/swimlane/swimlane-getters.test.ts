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
import { Dropzone, SwimlaneState } from "./type";
import { Card, ColumnDefinition, Swimlane } from "../../type";
import { RootState } from "../type";
import { createElement } from "../../helpers/jest/create-dom-element";

jest.mock("tlp");

describe("Swimlane state getters", () => {
    describe("is_loading_cards", () => {
        it("returns true if swimlanes are still loading", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: true
            } as SwimlaneState;

            expect(getters.is_loading_cards(state)).toBe(true);
        });

        it("returns true if swimlanes are loaded but at least one of swimlanes is still loading its children", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: false,
                swimlanes: [
                    { is_loading_children_cards: false } as Swimlane,
                    { is_loading_children_cards: true } as Swimlane,
                    { is_loading_children_cards: false } as Swimlane
                ]
            };

            expect(getters.is_loading_cards(state)).toBe(true);
        });

        it("returns false if swimlanes are loaded and their children are loaded", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: false,
                swimlanes: [
                    { is_loading_children_cards: false } as Swimlane,
                    { is_loading_children_cards: false } as Swimlane,
                    { is_loading_children_cards: false } as Swimlane
                ]
            };

            expect(getters.is_loading_cards(state)).toBe(false);
        });
    });

    describe("nb_cards_in_column", () => {
        let column: ColumnDefinition;
        beforeEach(() => {
            column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 7546 }] },
                    { tracker_id: 46, accepts: [{ id: 4366 }] }
                ]
            } as ColumnDefinition;
        });

        it("returns 0 if no swimlanes", () => {
            const state: SwimlaneState = {
                swimlanes: [] as Swimlane[]
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
                            has_children: true
                        },
                        children_cards: [
                            {
                                id: 2,
                                label: "children 2 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            },
                            {
                                id: 3,
                                label: "children 3 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            }
                        ]
                    },
                    {
                        card: {
                            id: 4,
                            label: "parent 4 is in column 7546 but has children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: true
                        },
                        children_cards: [
                            {
                                id: 5,
                                label: "children 5 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            },
                            {
                                id: 6,
                                label: "children 6 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            }
                        ]
                    }
                ]
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
                            has_children: true
                        },
                        children_cards: [
                            {
                                id: 2,
                                label: "children 2 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            },
                            {
                                id: 3,
                                label: "children 3 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            }
                        ]
                    },
                    {
                        card: {
                            id: 4,
                            label: "parent 4 is in column 7546 and has no children",
                            tracker_id: 45,
                            mapped_list_value: { id: 7546 },
                            has_children: false
                        }
                    },
                    {
                        card: {
                            id: 5,
                            label: "parent 5 is NOT in column",
                            tracker_id: 45,
                            mapped_list_value: { id: 8000 },
                            has_children: false
                        }
                    }
                ]
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
                            has_children: true
                        },
                        children_cards: [
                            {
                                id: 2,
                                label: "children 2 is NOT in column",
                                tracker_id: 45,
                                mapped_list_value: { id: 8000 }
                            },
                            {
                                id: 3,
                                label: "children 3 is in column 7546",
                                tracker_id: 45,
                                mapped_list_value: { id: 7546 }
                            }
                        ]
                    }
                ]
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
                mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }]
            } as ColumnDefinition;

            swimlane_state = { swimlanes: [] as Swimlane[] } as SwimlaneState;

            root_state = {
                column: {
                    columns: [column_todo]
                }
            } as RootState;
        });

        it("Should return the cards of the column", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: true } as Card,
                children_cards: [
                    { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                    { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                    { id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card
                ],
                is_loading_children_cards: false
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.cards_in_cell(swimlane_state, [], root_state)(swimlane, column_todo)
            ).toEqual([
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } },
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } }
            ]);
        });

        it("Should return an empty array if it is a solo card swimlane", () => {
            const swimlane: Swimlane = {
                card: { id: 43, has_children: false } as Card,
                children_cards: [],
                is_loading_children_cards: false
            } as Swimlane;

            swimlane_state.swimlanes.push(swimlane);

            expect(
                getters.cards_in_cell(swimlane_state, [], root_state)(swimlane, column_todo)
            ).toEqual([]);
        });
    });

    describe("column_and_swimlane_of_cell", () => {
        let swimlane_state: SwimlaneState;
        let root_state: RootState;
        let swimlane_to_find: Swimlane;
        let column_to_find: ColumnDefinition;

        beforeEach(() => {
            swimlane_to_find = { card: { id: 100 } as Card } as Swimlane;
            column_to_find = { id: 15, label: "Todo" } as ColumnDefinition;

            swimlane_state = {
                swimlanes: [swimlane_to_find]
            } as SwimlaneState;

            root_state = {
                column: {
                    columns: [column_to_find]
                }
            } as RootState;
        });

        it("shoud return the column and the swimlane referenced by the cell", () => {
            const target_cell = getCellElement(
                swimlane_to_find.card.id.toString(),
                column_to_find.id.toString()
            );

            const { swimlane, column } = getters.column_and_swimlane_of_cell(
                swimlane_state,
                [],
                root_state
            )(target_cell);

            if (!swimlane || !column) {
                throw new Error("swimlane or column have not been found");
            }

            expect(swimlane.card.id).toEqual(100);
            expect(column.label).toEqual("Todo");
        });

        it("should return an undefined swimlane or column if one or the other have not been found", () => {
            const target_cell = getCellElement("300", "200");

            const { swimlane, column } = getters.column_and_swimlane_of_cell(
                swimlane_state,
                [],
                root_state
            )(target_cell);

            expect(swimlane).toBeUndefined();
            expect(column).toBeUndefined();
        });
    });

    describe("has_at_least_one_card_in_edit_mode", () => {
        it("Returns false if there isn't any parent card in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane,
                    {
                        card: { remaining_effort: { is_in_edit_mode: false } },
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane
                ]
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(false);
        });
        it("Returns true if there is a parent card with remaining effort in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane,
                    {
                        card: { remaining_effort: { is_in_edit_mode: true } },
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane
                ]
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(true);
        });
        it("Returns true if there is a parent card in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane,
                    {
                        card: { is_in_edit_mode: true },
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane
                ]
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(true);
        });
        it("Returns true if there is a children card in edit mode", () => {
            const state = {
                swimlanes: [
                    {
                        card: {},
                        children_cards: [{} as Card, {} as Card]
                    } as Swimlane,
                    {
                        card: {},
                        children_cards: [{} as Card, { is_in_edit_mode: true } as Card]
                    } as Swimlane
                ]
            } as SwimlaneState;
            expect(getters.has_at_least_one_card_in_edit_mode(state)).toBe(true);
        });
    });

    describe("does_cell_reject_drop", () => {
        let state: SwimlaneState, swimlane: Swimlane, column: ColumnDefinition;

        beforeEach(() => {
            state = {
                last_hovered_drop_zone: undefined
            } as SwimlaneState;

            swimlane = {
                card: {
                    id: 200
                }
            } as Swimlane;

            column = { id: 12 } as ColumnDefinition;
        });

        it("returns false if no previous drop zone (DZ) is known", () => {
            const does_reject = getters.does_cell_reject_drop(state)(swimlane, column);

            expect(does_reject).toBe(false);
        });

        it("returns false if the DZ does not reject the drop", () => {
            state.last_hovered_drop_zone = {
                is_drop_rejected: false
            } as Dropzone;

            const does_reject = getters.does_cell_reject_drop(state)(swimlane, column);

            expect(does_reject).toBe(false);
        });

        it("returns false if the provided column id does not match with the known DZ column", () => {
            state.last_hovered_drop_zone = {
                is_drop_rejected: false,
                column_id: 50,
                swimlane_id: swimlane.card.id
            } as Dropzone;

            const does_reject = getters.does_cell_reject_drop(state)(swimlane, column);

            expect(does_reject).toBe(false);
        });

        it("returns false if the provided swimlane id does not match with the known DZ swimlane", () => {
            state.last_hovered_drop_zone = {
                is_drop_rejected: true,
                column_id: column.id,
                swimlane_id: 500
            } as Dropzone;

            const does_reject = getters.does_cell_reject_drop(state)(swimlane, column);

            expect(does_reject).toBe(false);
        });

        it("returns true when the provided coordinates match the DZ and drop is rejected", () => {
            state.last_hovered_drop_zone = {
                is_drop_rejected: true,
                column_id: column.id,
                swimlane_id: swimlane.card.id
            } as Dropzone;

            const does_reject = getters.does_cell_reject_drop(state)(swimlane, column);

            expect(does_reject).toBe(true);
        });
    });
});

function getCellElement(swimlane_id: string, column_id: string): HTMLElement {
    const target_cell = createElement();

    target_cell.setAttribute("data-swimlane-id", swimlane_id);
    target_cell.setAttribute("data-column-id", column_id);

    return target_cell;
}
