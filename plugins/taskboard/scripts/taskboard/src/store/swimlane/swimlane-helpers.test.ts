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
import { Swimlane } from "../../type";
import { findSwimlane, findSwimlaneIndex, replaceSwimlane } from "./swimlane-helpers";

describe(`swimlane-helpers`, () => {
    let state: SwimlaneState, second_swimlane: Swimlane;
    beforeEach(() => {
        const first_swimlane = { card: { id: 16 } } as Swimlane;
        second_swimlane = { card: { id: 24 } } as Swimlane;
        const third_swimlane = { card: { id: 35 } } as Swimlane;
        state = {
            swimlanes: [first_swimlane, second_swimlane, third_swimlane]
        } as SwimlaneState;
    });

    describe(`findSwimlane`, () => {
        it(`Given a swimlane,
            it will find the swimlane stored in the state with
            the same top-level card id`, () => {
            const needle = { card: { id: 24 } } as Swimlane;
            const result = findSwimlane(state, needle);
            expect(result).toBe(second_swimlane);
        });

        it(`Given an unknown swimlane,
            it will throw an error`, () => {
            const unknown_swimlane = { card: { id: 999 } } as Swimlane;
            expect(() => findSwimlane(state, unknown_swimlane)).toThrow();
        });
    });

    describe(`findSwimlaneIndex`, () => {
        it(`Given a swimlane, it will find its index in the state`, () => {
            const needle = { card: { id: 24 } } as Swimlane;
            const result = findSwimlaneIndex(state, needle);
            expect(result).toBe(1);
        });

        it(`Given an unknown swimlane, it will return -1`, () => {
            const unknown_swimlane = { card: { id: 999 } } as Swimlane;
            const result = findSwimlaneIndex(state, unknown_swimlane);
            expect(result).toBe(-1);
        });
    });

    describe(`replaceSwimlane`, () => {
        it(`Given a swimlane,
            it will find it in the state and replace it with the parameter`, () => {
            const replacement = ({
                card: { id: 24 },
                children_cards: [{ card: { id: 473 } }]
            } as unknown) as Swimlane;
            replaceSwimlane(state, replacement);

            const replacement_index = findSwimlaneIndex(state, replacement);
            expect(replacement_index).toBe(1);
            const replaced = state.swimlanes[replacement_index];
            expect(replaced).toBe(replacement);
        });

        it(`Given an unknown swimlane, it won't touch the state`, () => {
            const unknown_swimlane = { card: { id: 999 } } as Swimlane;

            replaceSwimlane(state, unknown_swimlane);

            const unknown_index = findSwimlaneIndex(state, unknown_swimlane);
            expect(unknown_index).toBe(-1);
        });
    });
});
