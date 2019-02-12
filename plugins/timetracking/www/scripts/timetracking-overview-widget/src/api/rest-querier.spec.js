/**
 * Copyright Enalean (c) 2019. All rights reserved.
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

import { tlp, mockFetchSuccess } from "tlp-mocks";

import { getTrackersFromReport } from "./rest-querier.js";

describe("Get Report() -", () => {
    it("the REST API will be queried : report with its trackers is returned", async () => {
        const report = [
            {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [{ id: 1, label: "timetracking_tracker" }]
            }
        ];
        mockFetchSuccess(tlp.get, {
            return_json: report
        });

        const result = await getTrackersFromReport(1);

        expect(tlp.get).toHaveBeenCalledWith("/api/v1/timetracking_reports/1");
        expect(result).toEqual([
            {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [{ id: 1, label: "timetracking_tracker" }]
            }
        ]);
    });
});

describe("Get Report's times() -", () => {
    it("the REST API will be queried : trackers withs artefacts and times are returned", async () => {
        const trackers = [
            {
                artifacts: [
                    {
                        minutes: 20
                    },
                    {
                        minutes: 40
                    }
                ],
                id: "16",
                label: "tracker",
                project: {},
                uri: ""
            },
            {
                artifacts: [
                    {
                        minutes: 20
                    }
                ],
                id: "18",
                label: "tracker 2",
                project: {},
                uri: ""
            }
        ];
        mockFetchSuccess(tlp.get, {
            return_json: trackers
        });

        const result = await getTrackersFromReport(1);

        expect(tlp.get).toHaveBeenCalledWith("/api/v1/timetracking_reports/1");
        expect(result).toEqual(trackers);
    });
});
