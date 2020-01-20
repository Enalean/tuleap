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

import { SwimlaneState } from "../type";
import { Tracker } from "../../../type";
import { UserForPeoplePicker } from "./type";

export const have_possible_assignees_been_loaded_for_tracker = (state: SwimlaneState) => (
    tracker: Tracker
): boolean => {
    return !tracker.assigned_to_field || state.possible_assignees.has(tracker.assigned_to_field.id);
};

export const assignable_users = (state: SwimlaneState) => (
    tracker: Tracker
): UserForPeoplePicker[] => {
    if (!tracker.assigned_to_field || !state.possible_assignees.has(tracker.assigned_to_field.id)) {
        return [];
    }

    return state.possible_assignees.get(tracker.assigned_to_field.id) || [];
};
