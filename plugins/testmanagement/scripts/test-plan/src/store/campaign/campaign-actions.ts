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
import { ActionContext } from "vuex";
import { RootState } from "../type";
import { recursiveGet } from "tlp";
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
                    context.commit("addCampaigns", collection);

                    return collection;
                },
            }
        );
    } finally {
        context.commit("endLoadingCampaigns");
    }
}
