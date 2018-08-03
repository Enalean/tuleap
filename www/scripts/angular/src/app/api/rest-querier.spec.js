/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { tlp } from "tlp-mocks";
import { getCampaigns } from "./rest-querier.js";

describe("rest querier", () => {
    describe("getCampaigns()", () => {
        it("Given a project id, a milestone id and a campaign status, then the campaigns with that status will be retrieved recursively and a promise will be resolved with all campaigns", async () => {
            const campaigns = [
                {
                    id: "6",
                    label: "Release 1",
                    status: "Open",
                    nb_of_passed: 0,
                    nb_of_failed: 0,
                    nb_of_notrun: 1,
                    nb_of_blocked: 0
                }
            ];

            tlp.recursiveGet.and.returnValue(Promise.resolve(campaigns));
            const project_id = 101;
            const milestone_id = 26;
            const campaign_status = "open";

            const result = await getCampaigns(project_id, milestone_id, campaign_status);

            expect(tlp.recursiveGet).toHaveBeenCalledWith(
                "/api/v1/projects/101/testmanagement_campaigns",
                {
                    params: {
                        limit: 10,
                        query: '{"status":"open","milestone_id":26}'
                    }
                }
            );
            expect(result).toEqual(campaigns);
        });
    });
});
