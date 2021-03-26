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

import * as tlp from "@tuleap/tlp-fetch";
import { getToBePlannedElements } from "./element-to-plan-retriever";
import type { Feature } from "../../type";

jest.mock("@tuleap/tlp-fetch");

describe("Tracker reports retriever", () => {
    it("retrieves tracker reports", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

        const expected_elements: Feature[] = [
            {
                id: 1,
                title: "My bug name",
                xref: "bug #1",
                tracker: {
                    label: "bug",
                    color_name: "plum_crazy",
                    id: 1,
                    uri: "/tracker/1",
                },
                background_color: "peggy_pink_text",
                has_user_story_linked: false,
            } as Feature,
            {
                id: 2,
                title: "My story",
                xref: "story #2",
                tracker: {
                    label: "story",
                    color_name: "flamingo_pink",
                    id: 2,
                    uri: "/tracker/2",
                },
                background_color: "",
                has_user_story_linked: false,
            } as Feature,
        ];

        recursiveGetSpy.mockResolvedValueOnce(expected_elements);

        const elements = await getToBePlannedElements(146);

        expect(elements).toBe(expected_elements);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/projects/146/program_backlog", {
            params: { limit: 50, offset: 0 },
        });
    });
});
