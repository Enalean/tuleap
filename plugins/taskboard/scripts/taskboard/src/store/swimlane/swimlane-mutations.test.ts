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

import { Card, Swimlane } from "../../type";
import * as mutations from "./swimlane-mutations";
import { SwimlaneState } from "./type";
import { findSwimlane, findSwimlaneIndex } from "./swimlane-helpers";

describe(`Swimlane state mutations`, () => {
    describe("addSwimlanes", () => {
        it("add swimlanes to existing ones and will keep the swimlanes sorted by rank", () => {
            const state: SwimlaneState = {
                swimlanes: [{ card: { id: 42, rank: 1 } }]
            } as SwimlaneState;
            mutations.addSwimlanes(state, [
                { card: { id: 43, rank: 3 } } as Swimlane,
                { card: { id: 44, rank: 2 } } as Swimlane
            ]);
            expect(state.swimlanes).toStrictEqual([
                { card: { id: 42, rank: 1 } },
                { card: { id: 44, rank: 2 } },
                { card: { id: 43, rank: 3 } }
            ]);
        });
    });

    describe("setIsLoadingSwimlanes", () => {
        it("set swimlane to loading state", () => {
            const state: SwimlaneState = {
                is_loading_swimlanes: false
            } as SwimlaneState;
            mutations.setIsLoadingSwimlanes(state, true);
            expect(state.is_loading_swimlanes).toStrictEqual(true);
        });
    });

    describe(`addChildrenToSwimlane`, () => {
        let state: SwimlaneState;
        const swimlane = ({
            card: { id: 86, rank: 1 },
            children_cards: [{ id: 188, rank: 20 }]
        } as unknown) as Swimlane;
        const unrelated_swimlane = ({
            card: { id: 19, rank: 2 },
            children_cards: []
        } as unknown) as Swimlane;
        const children_cards: Card[] = [
            { id: 189, rank: 22 } as Card,
            { id: 190, rank: 21 } as Card
        ];
        beforeEach(() => {
            state = { is_loading_swimlanes: false, swimlanes: [swimlane, unrelated_swimlane] };
        });

        it(`given a swimlane and children cards,
            it will add the children cards to the swimlane
            and keep the children cards sorted by rank`, () => {
            mutations.addChildrenToSwimlane(state, { swimlane, children_cards });

            const state_swimlane = findSwimlane(state, swimlane);
            expect(state_swimlane.children_cards).toStrictEqual([
                { id: 188, rank: 20 },
                { id: 190, rank: 21 },
                { id: 189, rank: 22 }
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
    });

    describe(`beginLoadingChildren`, () => {
        let state: SwimlaneState;
        const swimlane = { card: { id: 86 }, is_loading_children_cards: false } as Swimlane;
        const unrelated_swimlane = {
            card: { id: 19 },
            is_loading_children_cards: false
        } as Swimlane;
        beforeEach(() => {
            state = { is_loading_swimlanes: false, swimlanes: [swimlane, unrelated_swimlane] };
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
            is_loading_children_cards: true
        } as Swimlane;
        const swimlane = { card: { id: 86 }, is_loading_children_cards: true } as Swimlane;
        beforeEach(() => {
            state = { is_loading_swimlanes: false, swimlanes: [unrelated_swimlane, swimlane] };
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
});
