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
import { del, patch } from "tlp";
import { ColumnDefinition } from "../type";

export async function expandColumn(
    context: ActionContext<State, RootState>,
    column: ColumnDefinition
): Promise<void> {
    context.commit("expandColumn", column);

    const user_id = context.rootState.user.user_id;
    try {
        await del(
            `/api/v1/users/${encodeURIComponent(user_id)}/preferences?key=${encodeURIComponent(
                getPreferenceName(context, column)
            )}`
        );
    } catch (e) {
        // no display of error
        // we don't need to stop the flow of the users just because a user pref has not been saved
    }
}

export async function collapseColumn(
    context: ActionContext<State, RootState>,
    column: ColumnDefinition
): Promise<void> {
    context.commit("collapseColumn", column);

    const user_id = context.rootState.user.user_id;
    try {
        await patch(`/api/v1/users/${encodeURIComponent(user_id)}/preferences`, {
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                key: getPreferenceName(context, column),
                value: 1
            })
        });
    } catch (e) {
        // no display of error
        // we don't need to stop the flow of the users just because a user pref has not been saved
    }
}

function getPreferenceName(
    context: ActionContext<State, RootState>,
    column: ColumnDefinition
): string {
    return `plugin_taskboard_collapse_column_${context.rootState.milestone_id}_${column.id}`;
}
