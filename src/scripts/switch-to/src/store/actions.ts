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

import { ActionContext } from "vuex";
import { State } from "./type";
import { get } from "../../../../themes/tlp/src/js/fetch-wrapper";
import { UserHistory } from "../type";

export async function loadHistory(context: ActionContext<State, State>): Promise<void> {
    if (context.state.is_history_loaded) {
        return;
    }

    const response = await get(`/api/users/${context.state.user_id}/history`);
    const history: UserHistory = await response.json();
    context.commit("saveHistory", history);
}
