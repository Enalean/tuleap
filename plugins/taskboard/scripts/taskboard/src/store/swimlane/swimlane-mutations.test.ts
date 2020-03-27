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

import { Card, ColumnDefinition, Direction, Mapping, Swimlane } from "../../type";
import * as mutations from "./swimlane-mutations";
import { SwimlaneState } from "./type";
import { findSwimlane, findSwimlaneIndex } from "./swimlane-helpers";

describe(`Swimlane state mutations`, () => {
    describe("addSwimlanes", () => {
        it("add swimlanes to existing ones and will keep the swimlanes sorted by rank", () => {
            const state: SwimlaneState = {
                swimlanes: [{ card: { id: 42, rank: 1 } }],
            } as SwimlaneState;
            mutations.addSwimlanes(state, [
                { card: { id: 43, rank: 3 } } as Swimlane,
                { card: { id: 44, rank: 2 } } as Swimlane,
            ]);
            expect(state.swimlanes).toStrictEqual([
                { card: { id: 42, rank: 1 } },
                { card: { id: 44, rank: 2 } },
                { card: { id: 43, rank: 3 } },
            ]);
        });
    });

    describe("beginLoadingSwimlanes", () => {
        it("set swimlane to loading state", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: false,
            } as SwimlaneState;
            mutations.beginLoadingSwimlanes(state);
            expect(state.is_loading_swimlanes).toStrictEqual(true);
        });
    });

    describe("endLoadingSwimlanes", () => {
        it("set swimlane to no loading state", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: true,
            } as SwimlaneState;
            mutations.endLoadingSwimlanes(state);
            expect(state.is_loading_swimlanes).toStrictEqual(false);
        });
    });

    describe(`addChildrenToSwimlane`, () => {
        let state: SwimlaneState;
        const swimlane = ({
            card: { id: 86, rank: 1 },
            children_cards: [{ id: 188, rank: 20 }],
        } as unknown) as Swimlane;
        const unrelated_swimlane = ({
            card: { id: 19, rank: 2 },
            children_cards: [],
        } as unknown) as Swimlane;
        const children_cards: Card[] = [
            { id: 189, rank: 22 } as Card,
            { id: 190, rank: 21 } as Card,
        ];
        beforeEach(() => {
            state = {
                is_loading_swimlanes: false,
                swimlanes: [swimlane, unrelated_swimlane],
            } as SwimlaneState;
        });

        it(`given a swimlane and children cards,
            it will add the children cards to the swimlane
            and keep the children cards sorted by rank`, () => {
            mutations.addChildrenToSwimlane(state, { swimlane, children_cards });

            const state_swimlane = findSwimlane(state, swimlane);
            expect(state_swimlane.children_cards).toStrictEqual([
                { id: 188, rank: 20 },
                { id: 190, rank: 21 },
                { id: 189, rank: 22 },
            ]);
        });

        it(`will keep the swimlanes sorted by rank`, () => {
            mutations.addChildrenToSwimlane(state, { swimlane, children_cards });

            const indexOfFirstSwimlane = findSwimlaneIndex(state, swimlane);
            const indexOfSecondSwimlane = findSwimlaneIndex(state, unrelated_swimlane);

            expect(indexOfFirstSwimlane).toBeLessThan(indexOfSecondSwimlane);
        });

        it(`given a swimlane, it won't touch another swimlane's children cards`, () => {
            mutations.addChildrenToSwimlane(state, { swimlane, children_cards });

            const second_swimlane = findSwimlane(state, unrelated_swimlane);
            expect(second_swimlane.children_cards.length).toBe(0);
        });

        it(`Given a swimlane with no children, it adds children and updates the card property to inform that now there are children`, () => {
            const swimlane: Swimlane = {
                card: { id: 42, has_children: false },
                children_cards: [] as Card[],
            } as Swimlane;
            const state: SwimlaneState = {
                swimlanes: [swimlane],
            } as SwimlaneState;
            mutations.addChildrenToSwimlane(state, {
                swimlane,
                children_cards: [{ id: 1001 } as Card],
            });

            const state_swimlane = findSwimlane(state, swimlane);
            expect(state_swimlane.card.has_children).toBe(true);
        });

        it(`Given a swimlane with no children, it does not update the card if the new children list is empty`, () => {
            const swimlane: Swimlane = {
                card: { id: 42, has_children: false },
                children_cards: [] as Card[],
            } as Swimlane;
            const state: SwimlaneState = {
                swimlanes: [swimlane],
            } as SwimlaneState;
            mutations.addChildrenToSwimlane(state, { swimlane, children_cards: [] });

            const state_swimlane = findSwimlane(state, swimlane);
            expect(state_swimlane.card.has_children).toBe(false);
        });
    });

    describe(`refreshCard`, () => {
        it(`Given a swimlane and a card,
            when it is a solo swimlane,
            it will find it in the state and replace it`, () => {
            const swimlane: Swimlane = {
                card: {
                    id: 42,
                    label: "Solo card in state",
                    has_children: false,
                    mapped_list_value: { id: 1234, label: "Todo" },
                    initial_effort: 5,
                    remaining_effort: { value: 5 },
                } as Card,
                children_cards: [],
                is_loading_children_cards: false,
            };
            const state = {
                swimlanes: [
                    { card: { id: 21, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;
            const card = {
                id: 42,
                label: "Refreshed solo card",
                has_children: false,
                mapped_list_value: { id: 2345, label: "On going" },
                initial_effort: 4,
                remaining_effort: { value: 3 },
            } as Card;
            mutations.refreshCard(state, { refreshed_card: card });

            expect(state.swimlanes[1].card).toStrictEqual(card);
        });

        it(`Given a swimlane and a card,
            when it is a child card,
            it will find it in the state and replace it`, () => {
            const swimlane = {
                card: { id: 78, label: "Parent card", has_children: true } as Card,
                children_cards: [
                    { id: 61, label: "Unrelated card" } as Card,
                    {
                        id: 59,
                        label: "Child card in state",
                        mapped_list_value: { id: 1234, label: "Todo" },
                        initial_effort: 8,
                        remaining_effort: { value: 7 },
                    } as Card,
                ],
                is_loading_children_cards: false,
            };
            const state = {
                swimlanes: [
                    { card: { id: 21, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;
            const card = {
                id: 59,
                label: "Refreshed child card",
                mapped_list_value: { id: 2345, label: "On going" },
                initial_effort: 2,
                remaining_effort: { value: 0.5 },
            } as Card;
            mutations.refreshCard(state, { refreshed_card: card });

            expect(state.swimlanes[1].children_cards[1]).toStrictEqual(card);
        });

        it(`Given a swimlane and a card,
            when it is a child card without remaining effort,
            it will find it in the state and leave it as is`, () => {
            const swimlane = {
                card: { id: 78, label: "Parent card", has_children: true } as Card,
                children_cards: [
                    { id: 61, label: "Unrelated card" } as Card,
                    {
                        id: 59,
                        label: "Child card without remaining effort in state",
                        mapped_list_value: { id: 1234, label: "Todo" },
                        initial_effort: 8,
                        remaining_effort: null,
                    } as Card,
                ],
                is_loading_children_cards: false,
            };
            const state = {
                swimlanes: [
                    { card: { id: 21, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;
            const card = {
                id: 59,
                label: "Refreshed child card",
                mapped_list_value: { id: 2345, label: "On going" },
                initial_effort: 2,
                remaining_effort: null,
            } as Card;
            mutations.refreshCard(state, { refreshed_card: card });

            expect(state.swimlanes[1].children_cards[1]).toStrictEqual(card);
        });

        it(`Given a swimlane and a card,
            when it is a parent card,
            it will find it in the state and replace it`, () => {
            const swimlane = {
                card: {
                    id: 78,
                    label: "Parent card",
                    has_children: true,
                    mapped_list_value: { id: 1234, label: "Todo" },
                    initial_effort: 2,
                    remaining_effort: { value: 1.5 },
                } as Card,
                children_cards: [{ id: 61, label: "Unrelated card" } as Card],
                is_loading_children_cards: false,
            };
            const state = {
                swimlanes: [
                    { card: { id: 21, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;
            const card = {
                id: 78,
                label: "Refreshed parent card",
                has_children: true,
                mapped_list_value: { id: 3456, label: "Done" },
                initial_effort: 1,
                remaining_effort: { value: 0 },
            } as Card;
            mutations.refreshCard(state, { refreshed_card: card });

            expect(state.swimlanes[1].card).toStrictEqual(card);
        });

        it(`When the user is editing the remaining effort of the swimlane,
            it does not erase remaining effort's edit flags`, () => {
            const swimlane = {
                card: {
                    id: 78,
                    label: "Parent card",
                    has_children: true,
                    mapped_list_value: { id: 1234, label: "Todo" },
                    initial_effort: 2,
                    remaining_effort: {
                        value: 1.5,
                        is_in_edit_mode: true,
                        is_being_saved: false,
                        can_update: true,
                    },
                } as Card,
                children_cards: [{ id: 61, label: "Unrelated card" } as Card],
                is_loading_children_cards: false,
            };
            const state = {
                swimlanes: [
                    { card: { id: 21, label: "Unrelated swimlane" }, children_cards: [] },
                    swimlane,
                ],
            } as SwimlaneState;
            const card = {
                id: 78,
                label: "Refreshed parent card",
                has_children: true,
                mapped_list_value: { id: 3456, label: "Done" },
                initial_effort: 2,
                remaining_effort: { value: 0, can_update: true },
            } as Card;
            mutations.refreshCard(state, { refreshed_card: card });

            const state_swimlane = state.swimlanes[1];
            expect(state_swimlane.card.remaining_effort).not.toBeNull();
            if (state_swimlane.card.remaining_effort) {
                expect(state_swimlane.card.remaining_effort).toStrictEqual({
                    value: 0,
                    can_update: true,
                    is_in_edit_mode: true,
                    is_being_saved: false,
                });
            }
        });
    });

    describe(`beginLoadingChildren`, () => {
        let state: SwimlaneState;
        const swimlane = { card: { id: 86 }, is_loading_children_cards: false } as Swimlane;
        const unrelated_swimlane = {
            card: { id: 19 },
            is_loading_children_cards: false,
        } as Swimlane;
        beforeEach(() => {
            state = {
                is_loading_swimlanes: false,
                swimlanes: [swimlane, unrelated_swimlane],
            } as SwimlaneState;
        });

        it(`given a swimlane, it will set its loading flag to true`, () => {
            mutations.beginLoadingChildren(state, swimlane);

            const first_swimlane = findSwimlane(state, swimlane);
            expect(first_swimlane.is_loading_children_cards).toBe(true);
        });

        it(`given a swmilane, it won't touch another swimlane's loading flag`, () => {
            mutations.beginLoadingChildren(state, swimlane);

            const second_swimlane = findSwimlane(state, unrelated_swimlane);
            expect(second_swimlane.is_loading_children_cards).toBe(false);
        });
    });

    describe(`endLoadingChildren`, () => {
        let state: SwimlaneState;
        const unrelated_swimlane = {
            card: { id: 19 },
            is_loading_children_cards: true,
        } as Swimlane;
        const swimlane = { card: { id: 86 }, is_loading_children_cards: true } as Swimlane;
        beforeEach(() => {
            state = {
                is_loading_swimlanes: false,
                swimlanes: [unrelated_swimlane, swimlane],
            } as SwimlaneState;
        });

        it(`given a swimlane, it will set its loading flag to false`, () => {
            mutations.endLoadingChildren(state, swimlane);

            const first_swimlane = findSwimlane(state, swimlane);
            expect(first_swimlane.is_loading_children_cards).toBe(false);
        });

        it(`given a swimlane, it won't touch another swimlane's loading flag`, () => {
            mutations.endLoadingChildren(state, swimlane);

            const second_swimlane = findSwimlane(state, unrelated_swimlane);
            expect(second_swimlane.is_loading_children_cards).toBe(true);
        });
    });

    describe("collapseSwimlane", () => {
        it("collapses swimlane", () => {
            const swimlane: Swimlane = { card: { is_collapsed: false } } as Swimlane;

            const state: SwimlaneState = {
                swimlanes: [swimlane],
            } as SwimlaneState;

            mutations.collapseSwimlane(state, swimlane);
            expect(state.swimlanes[0].card.is_collapsed).toBe(true);
        });
    });

    describe("expandSwimlane", () => {
        it("expands swimlane", () => {
            const swimlane: Swimlane = { card: { is_collapsed: true } } as Swimlane;

            const state: SwimlaneState = {
                swimlanes: [swimlane],
            } as SwimlaneState;

            mutations.expandSwimlane(state, swimlane);
            expect(state.swimlanes[0].card.is_collapsed).toBe(false);
        });
    });

    describe("changeCardPosition", () => {
        let state: SwimlaneState;
        const card_to_move = { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card;
        const swimlane = {
            card: { id: 86 },
            children_cards: [
                { id: 100, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 101, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                card_to_move,
                { id: 103, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
            ],
        } as Swimlane;

        beforeEach(() => {
            state = { is_loading_swimlanes: false, swimlanes: [swimlane] } as SwimlaneState;
        });

        it("changes the position of the card", () => {
            mutations.changeCardPosition(state, {
                swimlane,
                column: { id: 10 } as ColumnDefinition,
                position: {
                    ids: [card_to_move.id],
                    direction: Direction.BEFORE, // Card is moved to the top
                    compared_to: 100,
                },
            });

            expect(swimlane.children_cards[0]).toEqual(card_to_move);
        });
    });

    describe("moveCardToColumn", () => {
        let state: SwimlaneState;
        let card_to_move: Card;
        let swimlane: Swimlane;

        beforeEach(() => {
            card_to_move = {
                id: 102,
                tracker_id: 7,
                mapped_list_value: { id: 48 },
                has_children: false,
            } as Card;
            swimlane = {
                card: card_to_move,
                children_cards: [] as Card[],
            } as Swimlane;
            state = { is_loading_swimlanes: false, swimlanes: [swimlane] } as SwimlaneState;
        });

        it(`When I move the card in an empty column
            It will not try to reorder the cards`, () => {
            const column = {
                id: 10,
                label: "Ongoing",
                mappings: [{ field_id: 1234, tracker_id: 7, accepts: [{ id: 50 }] } as Mapping],
            } as ColumnDefinition;

            jest.spyOn(mutations, "changeCardPosition");

            mutations.moveCardToColumn(state, {
                card: card_to_move,
                swimlane,
                column,
            });

            expect(mutations.changeCardPosition).not.toHaveBeenCalled();
            expect(card_to_move.mapped_list_value).toEqual({ id: 50, label: "Ongoing" });
        });

        it(`When I move a card at the top of another column containing some cards,
            It will move the card in the column and reorder the cards`, () => {
            const column = {
                id: 10,
                label: "Ongoing",
                mappings: [{ field_id: 1234, tracker_id: 7, accepts: [{ id: 50 }] } as Mapping],
            } as ColumnDefinition;

            swimlane.children_cards = [
                { id: 100, tracker_id: 7, mapped_list_value: { id: 49, label: "Todo" } } as Card,
                card_to_move,
                { id: 101, tracker_id: 7, mapped_list_value: { id: 49, label: "Todo" } } as Card,
                { id: 103, tracker_id: 7, mapped_list_value: { id: 50, label: "Ongoing" } } as Card,
            ];

            const position = {
                ids: [card_to_move.id],
                direction: Direction.BEFORE, // Card is moved to the top
                compared_to: 103,
            };

            mutations.moveCardToColumn(state, {
                card: card_to_move,
                swimlane,
                column,
                position,
            });

            expect(swimlane.card.mapped_list_value).toEqual({ id: 50, label: "Ongoing" });

            expect(swimlane.children_cards).toEqual([
                { id: 100, tracker_id: 7, mapped_list_value: { id: 49, label: "Todo" } } as Card,
                { id: 101, tracker_id: 7, mapped_list_value: { id: 49, label: "Todo" } } as Card,
                card_to_move,
                { id: 103, tracker_id: 7, mapped_list_value: { id: 50, label: "Ongoing" } } as Card,
            ]);
        });
    });

    describe("setColumnOfCard", () => {
        let state: SwimlaneState,
            card: Card,
            swimlane: Swimlane,
            solo_card_swimlane: Swimlane,
            solo_card: Card;

        beforeEach(() => {
            card = {
                id: 2,
                tracker_id: 45,
                mapped_list_value: { id: 9999 },
                has_children: true,
            } as Card;
            solo_card = {
                id: 3,
                tracker_id: 45,
                mapped_list_value: { id: 6666 },
                has_children: false,
            } as Card;

            swimlane = {
                card: {
                    id: 1,
                },
                children_cards: [card],
            } as Swimlane;

            solo_card_swimlane = {
                card: solo_card,
                children_cards: [] as Card[],
            } as Swimlane;

            state = {
                swimlanes: [swimlane, solo_card_swimlane],
            } as SwimlaneState;
        });

        it("Given a card and a column, then it should change the card's mapped_list_value", () => {
            const column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 5398 }] },
                    { tracker_id: 46, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;

            mutations.setColumnOfCard(state, { card, column, swimlane });

            const card_in_state = swimlane.children_cards[0];
            expect(card_in_state.mapped_list_value).toEqual({ id: 5398 });
        });

        it("Given a solo card and a column, then it should change the card's mapped_list_value", () => {
            const column = {
                mappings: [
                    { tracker_id: 45, accepts: [{ id: 5398 }] },
                    { tracker_id: 46, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;

            mutations.setColumnOfCard(state, {
                card: solo_card,
                column,
                swimlane: solo_card_swimlane,
            });

            expect(solo_card_swimlane.card.mapped_list_value).toEqual({ id: 5398 });
        });

        it("When no mapping exist with the card's tracker, then it should do nothing", () => {
            const column = {
                mappings: [
                    { tracker_id: 46, accepts: [{ id: 5398 }] },
                    { tracker_id: 47, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;

            mutations.setColumnOfCard(state, { card, column, swimlane });

            expect(card.mapped_list_value).toEqual({ id: 9999 });
        });

        it("When the column do not accept any value for the card's tracker, then it should do nothing", () => {
            const column = {
                mappings: [
                    { tracker_id: 45, accepts: [] },
                    { tracker_id: 47, accepts: [{ id: 4366 }] },
                ],
            } as ColumnDefinition;

            mutations.setColumnOfCard(state, { card, column, swimlane });

            expect(card.mapped_list_value).toEqual({ id: 9999 });
        });
    });
});
