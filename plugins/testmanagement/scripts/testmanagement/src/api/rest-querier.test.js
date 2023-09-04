/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { getCampaigns, getDefinitions } from "./rest-querier.js";

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
                    nb_of_blocked: 0,
                },
            ];

            const tlpRecursiveGetSpy = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockReturnValue(Promise.resolve(campaigns));
            const project_id = 101;
            const milestone_id = 26;
            const campaign_status = "open";

            const result = await getCampaigns(project_id, milestone_id, campaign_status);

            expect(tlpRecursiveGetSpy).toHaveBeenCalledWith(
                "/api/v1/projects/101/testmanagement_campaigns",
                {
                    params: {
                        limit: 10,
                        query: '{"status":"open","milestone_id":26}',
                    },
                },
            );
            expect(result).toEqual(campaigns);
        });
    });

    describe("getDefinitions()", () => {
        it("Given a project id and a report id, then the test definitions will be retrieved recursively and a promise will be resolved with all definitions", async () => {
            const definitions = [
                { id: 1, summary: "plumber" },
                { id: 86, summary: "disguisement" },
            ];

            const tlpRecursiveGetSpy = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockReturnValue(Promise.resolve(definitions));
            const project_id = 77;
            const report_id = 53;

            const result = await getDefinitions(project_id, report_id);

            expect(tlpRecursiveGetSpy).toHaveBeenCalledWith(
                "/api/v1/projects/77/testmanagement_definitions",
                {
                    params: {
                        limit: 100,
                        report_id,
                    },
                },
            );
            expect(result).toEqual(definitions);
        });

        it("Given no report id, then the test definitions will be retrieved without it", async () => {
            const definitions = [
                { id: 22, summary: "polymazia" },
                { id: 72, summary: "pilastering" },
            ];

            const tlpRecursiveGetSpy = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockReturnValue(Promise.resolve(definitions));
            const project_id = 6;

            await getDefinitions(project_id);

            expect(tlpRecursiveGetSpy).toHaveBeenCalledWith(
                "/api/v1/projects/6/testmanagement_definitions",
                {
                    params: {
                        limit: 100,
                    },
                },
            );
        });
    });
});
