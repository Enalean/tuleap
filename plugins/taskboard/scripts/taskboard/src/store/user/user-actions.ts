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
import type { UserPreference, UserPreferenceValue, UserState } from "./type";
import type { RootState } from "../type";
import { del, patch } from "@tuleap/tlp-fetch";

export async function setPreference(
    context: ActionContext<UserState, RootState>,
    preference: UserPreferenceValue,
): Promise<void> {
    const user_id = context.state.user_id;
    if (!user_id) {
        return;
    }

    await patch(`/api/v1/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(preference),
    }).catch(() => {
        // no display of error
        // we don't need to stop the flow of the users just because a user pref has not been saved
    });
}

export async function deletePreference(
    context: ActionContext<UserState, RootState>,
    preference: UserPreference,
): Promise<void> {
    const user_id = context.state.user_id;
    if (!user_id) {
        return;
    }

    await del(
        `/api/v1/users/${encodeURIComponent(user_id)}/preferences?key=${encodeURIComponent(
            preference.key,
        )}`,
    ).catch(() => {
        // no display of error
        // we don't need to stop the flow of the users just because a user pref has not been saved
    });
}
