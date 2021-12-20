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
import { retrieveUnplannedElements } from "./increment-unplanned-elements-retriever";

import type { UserStory } from "../type";

jest.mock("tlp");

describe("increment-unplanned-elements-retriever", () => {
    it("retrieves the unplanned elements of a given iteration", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");
        const increment_iterations: UserStory[] = [
            {
                background_color: "peggy-pink",
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
                feature: null,
            },
        ];

        recursiveGetSpy.mockResolvedValueOnce(increment_iterations);

        const iterations = await retrieveUnplannedElements(666);

        expect(iterations).toBe(increment_iterations);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/program_increment/666/backlog", {
            params: { limit: 50, offset: 0 },
        });
    });
});
