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
import * as tlp from "tlp";
import { loadCampaigns } from "./campaign-actions";

jest.mock("tlp");

describe("Campaign state actions", () => {
    let context: ActionContext<CampaignState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            rootState: {
                milestone_id: 42,
                project_id: 104,
            } as RootState,
        } as unknown) as ActionContext<CampaignState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
    });

    describe("loadCampaigns", () => {
        it("Retrieves all campaigns for milestone", async () => {
            await loadCampaigns(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingCampaigns");
            expect(context.commit).toHaveBeenCalledWith("endLoadingCampaigns");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
                `/api/v1/projects/104/testmanagement_campaigns`,
                {
                    params: { query: '{"milestone_id":42}', limit: 100 },
                    getCollectionCallback: expect.any(Function),
                }
            );
        });
    });
});
