/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { getNbOfBacklogItems, getNbOfUpcomingReleases } from "./rest-querier.js";
import { mockFetchSuccess, tlp } from "tlp-mocks";

describe("getProject() -", () => {
    const limit = 2,
        offset = 0,
        project_id = 102;

    it("the REST API will be queried and the project's backlog returned", async () => {
        mockFetchSuccess(tlp.get, {
            headers: {
                get: header_name => {
                    const headers = {
                        "X-PAGINATION-SIZE": 2
                    };
                    return headers[header_name];
                }
            }
        });

        const result = await getNbOfBacklogItems(project_id, limit, offset);
        expect(tlp.get).toHaveBeenCalledWith("/api/v1/projects/" + project_id + "/backlog", {
            params: { limit, offset }
        });

        expect(result).toEqual(2);
    });

    it("the REST API will be queried and the milestones planned returned", async () => {
        mockFetchSuccess(tlp.get, {
            headers: {
                get: header_name => {
                    const headers = {
                        "X-PAGINATION-SIZE": 2
                    };

                    return headers[header_name];
                }
            }
        });

        const result = await getNbOfUpcomingReleases(project_id, limit, offset);

        let query = JSON.stringify({
            period: "future"
        });

        expect(tlp.get).toHaveBeenCalledWith("/api/v1/projects/" + project_id + "/milestones", {
            params: {
                limit,
                offset,
                query
            }
        });

        expect(result).toEqual(2);
    });
});
