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
import type { UserStory } from "./user-stories-retriever";
import { getLinkedUserStoriesToFeature } from "./user-stories-retriever";
import type { TrackerMinimalRepresentation } from "../../type";

describe("User stories retriever", () => {
    describe("getLinkedUserStoriesToFeature", () => {
        it("Get children of a feature", async () => {
            const recursiveGet = jest.spyOn(tlp, "recursiveGet");

            const features: UserStory[] = [
                {
                    id: 1,
                    is_open: true,
                    title: "My US",
                    uri: "/tracker?aid=1",
                    xref: "US #1",
                    project: {
                        id: 101,
                        label: "My Team Project",
                        uri: "project/team",
                        icon: "",
                    },
                    background_color: "fiesta-red",
                    tracker: {
                        color_name: "lake-placid-blue",
                    } as TrackerMinimalRepresentation,
                },
                {
                    id: 2,
                    is_open: false,
                    title: "My closed bug",
                    uri: "/tracker?aid=2",
                    xref: "bug #2",
                    project: {
                        id: 101,
                        label: "My Team Project",
                        uri: "project/team",
                        icon: "",
                    },
                    background_color: "fiesta-red",
                    tracker: {
                        color_name: "lake-placid-blue",
                    } as TrackerMinimalRepresentation,
                },
            ];

            recursiveGet.mockResolvedValueOnce(features);

            const increments = await getLinkedUserStoriesToFeature(146);

            expect(increments).toBe(features);
            expect(recursiveGet).toHaveBeenCalledWith(
                "/api/v1/program_backlog_items/146/children",
                {
                    params: { limit: 50, offset: 0 },
                },
            );
        });
    });
});
