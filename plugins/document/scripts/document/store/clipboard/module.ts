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

import * as mutations from "./clipboard-mutations";
import * as actions from "./clipboard-actions";
import defaultState from "./clipboard-default-state";

export interface ClipboardState {
    item_id: null | number;
    item_title: null | string;
    item_type: null | string;
    operation_type: null | string;
    pasting_in_progress: boolean;
}

export default {
    namespaced: true,
    state: defaultState(),
    mutations,
    actions,
};
