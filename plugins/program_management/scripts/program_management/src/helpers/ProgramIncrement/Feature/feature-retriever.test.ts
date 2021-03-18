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
import type { Feature } from "./feature-retriever";
import { getFeatures } from "./feature-retriever";

jest.mock("tlp");

describe("Features retriever", () => {
    it("retrieves feature planned in a program increment", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

        const features: Feature[] = [
            {
                artifact_id: 1,
                artifact_title: "My artifact",
                artifact_xref: "art #1",
                background_color: "",
                tracker: {
                    id: 100,
                    label: "bug",
                    color_name: "",
                    uri: "/tracker/100",
                },
                has_user_story_planned: false,
            },
            {
                artifact_id: 2,
                artifact_title: "My story",
                artifact_xref: "art #2",
                background_color: "",
                tracker: {
                    id: 200,
                    label: "story",
                    color_name: "",
                    uri: "/tracker/200",
                },
                has_user_story_planned: false,
            },
        ];

        recursiveGetSpy.mockResolvedValueOnce(features);

        const increments = await getFeatures(146);

        expect(increments).toBe(features);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/program_increment/146/content", {
            params: { limit: 50, offset: 0 },
        });
    });
});
