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
import type { ToBePlannedElement } from "./element-to-plan-retriever";
import { getToBePlannedElements } from "./element-to-plan-retriever";

jest.mock("tlp");

describe("Tracker reports retriever", () => {
    it("retrieves tracker reports", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

        const expected_elements: ToBePlannedElement[] = [
            {
                artifact_id: 1,
                artifact_title: "My bug name",
                artifact_xref: "bug #1",
                tracker: {
                    label: "bug",
                    color_name: "plum_crazy",
                    id: 1,
                    uri: "/tracker/1",
                },
                background_color: "peggy_pink_text",
            },
            {
                artifact_id: 2,
                artifact_title: "My story",
                artifact_xref: "story #2",
                tracker: {
                    label: "story",
                    color_name: "flamingo_pink",
                    id: 2,
                    uri: "/tracker/2",
                },
                background_color: "",
            },
        ];

        recursiveGetSpy.mockResolvedValueOnce(expected_elements);

        const elements = await getToBePlannedElements(146);

        expect(elements).toBe(expected_elements);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/projects/146/program_backlog", {
            params: { limit: 50, offset: 0 },
        });
    });
});
