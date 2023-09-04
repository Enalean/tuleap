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

import type { ActionContext } from "vuex";
import type { RootState } from "../type";
import type { ColumnDefinition } from "../../type";
import type { UserPreference, UserPreferenceValue } from "../user/type";
import type { ColumnState } from "./type";

export function expandColumn(
    context: ActionContext<ColumnState, RootState>,
    column: ColumnDefinition,
): Promise<void> {
    context.commit("expandColumn", column);
    const payload: UserPreference = {
        key: getCollapsePreferenceName(context, column),
    };

    return context.dispatch("user/deletePreference", payload, { root: true });
}

export function collapseColumn(
    context: ActionContext<ColumnState, RootState>,
    column: ColumnDefinition,
): Promise<void> {
    context.commit("collapseColumn", column);
    const payload: UserPreferenceValue = {
        key: getCollapsePreferenceName(context, column),
        value: "1",
    };

    return context.dispatch("user/setPreference", payload, { root: true });
}

function getCollapsePreferenceName(
    context: ActionContext<ColumnState, RootState>,
    column: ColumnDefinition,
): string {
    return `plugin_taskboard_collapse_column_${context.rootState.milestone_id}_${column.id}`;
}
