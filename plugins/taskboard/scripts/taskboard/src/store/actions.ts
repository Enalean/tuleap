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

import { ActionContext } from "vuex";
import { RootState, State } from "./type";
import { UserPreference, UserPreferenceValue } from "./user/type";

export function displayClosedItems(context: ActionContext<State, RootState>): Promise<void> {
    context.commit("displayClosedItems");
    const payload: UserPreference = {
        key: getHideClosedPreferenceName(context),
    };

    return context.dispatch("user/deletePreference", payload, { root: true });
}

export function hideClosedItems(context: ActionContext<State, RootState>): Promise<void> {
    context.commit("hideClosedItems");
    const payload: UserPreferenceValue = {
        key: getHideClosedPreferenceName(context),
        value: "1",
    };

    return context.dispatch("user/setPreference", payload, { root: true });
}

function getHideClosedPreferenceName(context: ActionContext<State, RootState>): string {
    return `plugin_taskboard_hide_closed_items_${context.rootState.milestone_id}`;
}
