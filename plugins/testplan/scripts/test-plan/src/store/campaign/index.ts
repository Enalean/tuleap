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

import { Module } from "vuex";
import { CampaignState } from "./type";
import { RootState } from "../type";
import * as actions from "./campaign-actions";
import * as mutations from "./campaign-mutations";

const campaign_module_default: Module<CampaignState, RootState> = {
    namespaced: true,
    state: {
        campaigns: [],
        is_loading: true,
        has_loading_error: false,
        has_refreshing_error: false,
    },
    actions,
    mutations,
};
export default campaign_module_default;
