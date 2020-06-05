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

import { CampaignState } from "./type";
import { Campaign } from "../../type";
import {
    addCampaigns,
    beginLoadingCampaigns,
    endLoadingCampaigns,
    errorHasBeenCatched,
} from "./campaign-mutations";

describe("Campaign state mutations", () => {
    it("beginLoadingCampaigns", () => {
        const state: CampaignState = {
            is_loading: false,
            is_error: false,
            campaigns: [],
        };

        beginLoadingCampaigns(state);

        expect(state.is_loading).toBe(true);
    });

    it("endLoadingCampaigns", () => {
        const state: CampaignState = {
            is_loading: true,
            is_error: false,
            campaigns: [],
        };

        endLoadingCampaigns(state);

        expect(state.is_loading).toBe(false);
    });

    it("addCampaigns", () => {
        const state: CampaignState = {
            is_loading: true,
            is_error: false,
            campaigns: [{ id: 1 } as Campaign],
        };

        addCampaigns(state, [{ id: 2 }, { id: 3 }] as Campaign[]);

        expect(state.campaigns.length).toBe(3);
    });

    it("errorHasBeenCatched", () => {
        const state: CampaignState = {
            is_loading: true,
            is_error: false,
            campaigns: [],
        };

        errorHasBeenCatched(state);

        expect(state.is_error).toBe(true);
    });
});
