/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { Settings } from "luxon";
import { tlp, mockFetchSuccess } from "tlp-mocks";

import { getTrackedTimes, addTime } from "./rest-querier.js";

describe("getTrackedTimes() -", () => {
    it("the REST API will be queried with ISO-8601 dates and the times returned", async () => {
        Settings.defaultZoneName = "Europe/Paris";
        const limit = 1,
            offset = 0,
            user_id = 102;

        const times = [
            [
                {
                    artifact: {},
                    project: {},
                    minutes: 20
                }
            ]
        ];

        mockFetchSuccess(tlp.get, {
            headers: {
                get: header_name => {
                    const headers = {
                        "X-PAGINATION-SIZE": 1
                    };

                    return headers[header_name];
                }
            },
            return_json: times
        });

        const result = await getTrackedTimes(user_id, "2018-03-08", "2018-03-15", limit, offset);
        expect(tlp.get).toHaveBeenCalledWith("/api/v1/users/" + user_id + "/timetracking", {
            params: {
                limit,
                offset,
                query: JSON.stringify({
                    start_date: "2018-03-08T00:00:00+01:00",
                    end_date: "2018-03-15T00:00:00+01:00"
                })
            }
        });

        expect(result.times).toEqual(times);
        expect(result.total).toEqual(1);
    });

    it("the REST API will add date and the new time should be returned", async () => {
        Settings.defaultZoneName = "Europe/Paris";
        const time = {
            artifact: {},
            project: {},
            minutes: 20
        };

        mockFetchSuccess(tlp.post, {
            return_json: time
        });
        const result = await addTime("2018-03-08", 2, "11:11", "oui");
        const headers = {
            "content-type": "application/json"
        };
        const body = JSON.stringify({
            date_time: "2018-03-08",
            artifact_id: 2,
            time_value: "11:11",
            step: "oui"
        });
        expect(tlp.post).toHaveBeenCalledWith("/api/v1/timetracking", {
            headers,
            body
        });
        expect(result).toEqual(time);
    });
});
