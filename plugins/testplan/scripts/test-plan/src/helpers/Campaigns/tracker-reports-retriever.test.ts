/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import * as tlp from "tlp";
import { getTrackerReports, TrackerReport } from "./tracker-reports-retriever";

jest.mock("tlp");

describe("Tracker reports retriever", () => {
    it("retrieves tracker reports", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

        const expected_reports: TrackerReport[] = [
            { id: 14, label: "Report 14" },
            { id: 15, label: "Report 15" },
        ];

        recursiveGetSpy.mockResolvedValueOnce(expected_reports);

        const reports = await getTrackerReports(146);

        expect(reports).toBe(expected_reports);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/trackers/146/tracker_reports");
    });
});
