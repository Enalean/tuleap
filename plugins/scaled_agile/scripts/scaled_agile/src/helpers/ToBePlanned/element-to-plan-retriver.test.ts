/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { getToBePlannedElements, ToBePlannedElement } from "./element-to-plan-retriver";

jest.mock("tlp");

describe("Tracker reports retriever", () => {
    it("retrieves tracker reports", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

        const expected_elements: ToBePlannedElement[] = [
            {
                artifact_id: 1,
                tracker_name: "bug",
                artifact_title: "My bug name",
            },
            {
                artifact_id: 2,
                tracker_name: "story",
                artifact_title: "My story",
            },
        ];

        recursiveGetSpy.mockResolvedValueOnce(expected_elements);

        const elements = await getToBePlannedElements(146);

        expect(elements).toBe(expected_elements);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/projects/146/scaled_agile_backlog", {
            params: { limit: 50, offset: 0 },
        });
    });
});
