/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import * as getters from "./card-getters";
import type { SwimlaneState } from "../type";
import type { UserForPeoplePicker } from "./type";
import type { Tracker } from "../../../type";

describe("Card getters", () => {
    let state: SwimlaneState;

    beforeEach(() => {
        state = {
            swimlanes: [],
            is_loading_swimlanes: false,
            dropzone_rejecting_drop: undefined,
            is_card_creation_blocked_due_to_ongoing_creation: false,
            possible_assignees: new Map<number, UserForPeoplePicker[]>(),
        };
    });

    describe("have_possible_assignees_been_loaded_for_tracker", () => {
        it("Returns true when the tracker has no assigned_to field", () => {
            const tracker = {
                assigned_to_field: null,
            } as Tracker;

            expect(getters.have_possible_assignees_been_loaded_for_tracker(state)(tracker)).toBe(
                true,
            );
        });

        it("Returns true when the assignees of the tracker have already been loaded", () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            state.possible_assignees.set(1234, [{ id: 1 } as UserForPeoplePicker]);

            expect(getters.have_possible_assignees_been_loaded_for_tracker(state)(tracker)).toBe(
                true,
            );
        });

        it("Returns false when the assignees of the tracker have not been loaded yet", () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            expect(getters.have_possible_assignees_been_loaded_for_tracker(state)(tracker)).toBe(
                false,
            );
        });
    });

    describe("assignable_users", () => {
        it("Returns an empty list when the tracker has no assigned_to field", () => {
            const tracker = {
                assigned_to_field: null,
            } as Tracker;

            expect(getters.assignable_users(state)(tracker)).toEqual([]);
        });

        it("Returns an empty list when tracker assignees have not been loaded yet", () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            expect(getters.assignable_users(state)(tracker)).toEqual([]);
        });

        it("Returns the list of assignale users", () => {
            const tracker = {
                assigned_to_field: {
                    id: 1234,
                },
            } as Tracker;

            state.possible_assignees.set(1234, [{ id: 1 } as UserForPeoplePicker]);

            expect(getters.assignable_users(state)(tracker)).toEqual([{ id: 1 }]);
        });
    });
});
