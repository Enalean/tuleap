/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import { mockFetchSuccess } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";

import { getTrackedTimes, addTime, deleteTime } from "./rest-querier.js";

import * as tlp from "tlp";

jest.mock("tlp");

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
                    minutes: 20,
                },
            ],
        ];

        const tlpGet = jest.spyOn(tlp, "get");
        mockFetchSuccess(tlpGet, {
            headers: {
                get: (header_name) => {
                    const headers = {
                        "X-PAGINATION-SIZE": 1,
                    };

                    return headers[header_name];
                },
            },
            return_json: times,
        });

        const result = await getTrackedTimes(user_id, "2018-03-08", "2018-03-15", limit, offset);
        expect(tlpGet).toHaveBeenCalledWith("/api/v1/users/" + user_id + "/timetracking", {
            params: {
                limit,
                offset,
                query: JSON.stringify({
                    start_date: "2018-03-08T00:00:00+01:00",
                    end_date: "2018-03-15T00:00:00+01:00",
                }),
            },
        });

        expect(result.times).toEqual(times);
        expect(result.total).toEqual(1);
    });

    it("the REST API will add date and the new time should be returned", async () => {
        Settings.defaultZoneName = "Europe/Paris";
        const time = {
            artifact: {},
            project: {},
            minutes: 20,
        };

        const tlpPost = jest.spyOn(tlp, "post");
        mockFetchSuccess(tlpPost, {
            return_json: time,
        });
        const result = await addTime("2018-03-08", 2, "11:11", "oui");
        const headers = {
            "content-type": "application/json",
        };
        const body = JSON.stringify({
            date_time: "2018-03-08",
            artifact_id: 2,
            time_value: "11:11",
            step: "oui",
        });
        expect(tlpPost).toHaveBeenCalledWith("/api/v1/timetracking", {
            headers,
            body,
        });
        expect(result).toEqual(time);
    });

    it("the REST API should delete the given time", async () => {
        Settings.defaultZoneName = "Europe/Paris";

        const tlpDel = jest.spyOn(tlp, "del");
        mockFetchSuccess(tlpDel, {
            return_json: [],
        });
        const time_id = 2;
        await deleteTime(time_id);
        const headers = {
            "content-type": "application/json",
        };
        expect(tlpDel).toHaveBeenCalledWith("/api/v1/timetracking/" + time_id, {
            headers,
        });
    });
});
