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
import { getFeatures } from "./feature-retriever";
import type { Feature } from "../../../type";

jest.mock("@tuleap/tlp-fetch");

describe("Features retriever", () => {
    it("retrieves feature planned in a program increment", async () => {
        const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

        const features: Feature[] = [
            {
                id: 1,
                title: "My artifact",
                xref: "art #1",
                background_color: "",
                tracker: {
                    id: 100,
                    label: "bug",
                    color_name: "",
                    uri: "/tracker/100",
                },
                has_user_story_planned: false,
                has_user_story_linked: false,
            } as Feature,
            {
                id: 2,
                title: "My story",
                xref: "art #2",
                background_color: "",
                tracker: {
                    id: 200,
                    label: "story",
                    color_name: "",
                    uri: "/tracker/200",
                },
                has_user_story_planned: false,
                has_user_story_linked: false,
            } as Feature,
        ];

        recursiveGetSpy.mockResolvedValueOnce(features);

        const increments = await getFeatures(146);

        expect(increments).toBe(features);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/program_increment/146/content", {
            params: { limit: 50, offset: 0 },
        });
    });
});
