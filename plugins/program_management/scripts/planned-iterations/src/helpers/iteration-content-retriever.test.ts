/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { retrieveIterationContent } from "./iteration-content-retriever";

import type { Feature } from "../type";

jest.mock("tlp");

describe("iteration-content-retriever", () => {
    it("retrieves the content of a given iteration", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");
        const increment_iterations: Feature[] = [
            {
                background_color: "peggy-pink",
                has_user_story_planned: false,
                has_user_story_linked: false,
                is_open: true,
                id: 101,
                uri: "/uri/of/feature",
                xref: "feature #101",
                title: "Feature 101",
                tracker: {
                    color_name: "red-wine",
                },
                project: {
                    id: 101,
                    uri: "uri/to/g-pig",
                    label: "Guinea Pigs",
                    icon: "🐹",
                },
            },
        ];

        recursiveGetSpy.mockResolvedValueOnce(increment_iterations);

        const iterations = await retrieveIterationContent(666);

        expect(iterations).toBe(increment_iterations);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/iteration/666/content", {
            params: { limit: 50, offset: 0 },
        });
    });
});
