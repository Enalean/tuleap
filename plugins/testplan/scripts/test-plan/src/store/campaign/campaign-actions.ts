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

import { CampaignState, CreateCampaignPayload } from "./type";
import { ActionContext } from "vuex";
import { RootState } from "../type";
import { recursiveGet, post, get } from "tlp";
import { Campaign } from "../../type";

export async function loadCampaigns(
    context: ActionContext<CampaignState, RootState>
): Promise<void> {
    context.commit("beginLoadingCampaigns");
    try {
        await recursiveGet(
            `/api/v1/projects/${context.rootState.project_id}/testmanagement_campaigns`,
            {
                params: {
                    query: JSON.stringify({
                        milestone_id: context.rootState.milestone_id,
                    }),
                    limit: 100,
                },
                getCollectionCallback: (collection: Campaign[]): Campaign[] => {
                    const campaigns: Campaign[] = collection.map(
                        (campaign: Campaign): Campaign => ({
                            ...campaign,
                            is_being_refreshed: false,
                            is_just_refreshed: false,
                            is_error: false,
                        })
                    );
                    context.commit("addCampaigns", campaigns);

                    return campaigns;
                },
            }
        );
    } catch (e) {
        context.commit("loadingErrorHasBeenCatched");
        throw e;
    } finally {
        context.commit("endLoadingCampaigns");
    }
}

export async function createCampaign(
    context: ActionContext<CampaignState, RootState>,
    payload: CreateCampaignPayload
): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        project_id: context.rootState.project_id,
        label: payload.label,
    });

    const response = await post(
        `/api/v1/testmanagement_campaigns?milestone_id=${context.rootState.milestone_id}&test_selector=${payload.test_selector}`,
        { headers, body }
    );
    const new_campaign = await response.json();
    const campaign: Campaign = {
        id: new_campaign.id,
        label: payload.label,
        nb_of_notrun: 0,
        nb_of_blocked: 0,
        nb_of_failed: 0,
        nb_of_passed: 0,
        is_being_refreshed: true,
        is_just_refreshed: false,
        is_error: false,
    };
    context.commit("addNewCampaign", campaign);

    return context.dispatch("refreshCampaign", campaign);
}

export async function refreshCampaign(
    context: ActionContext<CampaignState, RootState>,
    campaign: Campaign
): Promise<void> {
    try {
        const response = await get(`/api/v1/testmanagement_campaigns/${campaign.id}`);
        const new_campaign = await response.json();

        const updated_campaign: Campaign = {
            ...campaign,
            nb_of_blocked: new_campaign.nb_of_blocked,
            nb_of_passed: new_campaign.nb_of_passed,
            nb_of_notrun: new_campaign.nb_of_notrun,
            nb_of_failed: new_campaign.nb_of_failed,
        };
        context.commit("updateCampaignAfterCreation", updated_campaign);
        context.commit("removeHasRefreshingErrorFlag");
    } catch (e) {
        const updated_campaign: Campaign = {
            ...campaign,
            is_error: true,
        };
        context.commit("updateCampaignAfterCreation", updated_campaign);
        context.commit("refreshingloadingErrorHasBeenCatched");
        throw e;
    }
}
