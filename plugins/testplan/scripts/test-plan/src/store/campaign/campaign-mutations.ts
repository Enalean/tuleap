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

import type { CampaignState } from "./type";
import type { Campaign } from "../../type";

export function beginLoadingCampaigns(state: CampaignState): void {
    state.is_loading = true;
}

export function endLoadingCampaigns(state: CampaignState): void {
    state.is_loading = false;
}

export function addCampaigns(state: CampaignState, collection: Campaign[]): void {
    state.campaigns = state.campaigns.concat(collection);
}

export function loadingErrorHasBeenCatched(state: CampaignState): void {
    state.has_loading_error = true;
}

export function refreshingloadingErrorHasBeenCatched(state: CampaignState): void {
    state.has_refreshing_error = true;
}

export function removeHasRefreshingErrorFlag(state: CampaignState): void {
    state.has_refreshing_error = false;
}

export function addNewCampaign(state: CampaignState, campaign: Campaign): void {
    state.campaigns = [campaign, ...state.campaigns];
}

export function updateCampaignAfterCreation(state: CampaignState, campaign: Campaign): void {
    updateCampaign(state, { ...campaign, is_being_refreshed: false, is_just_refreshed: true });
    setTimeout(() => {
        updateCampaign(state, { ...campaign, is_being_refreshed: false, is_just_refreshed: false });
    }, 1000);
}

function updateCampaign(state: CampaignState, campaign: Campaign): void {
    const index = state.campaigns.findIndex(
        (state_campaign: Campaign): boolean => state_campaign.id === campaign.id,
    );
    if (index === -1) {
        throw Error("Unable to find the campaign to update");
    }

    state.campaigns.splice(index, 1, campaign);
}
