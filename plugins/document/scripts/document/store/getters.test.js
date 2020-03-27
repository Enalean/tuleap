/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import * as getters from "./getters.js";

describe("Store getters", () => {
    describe("current_folder_title", () => {
        it("returns the title of the last item in the ascendant hierarchy", () => {
            const title = getters.current_folder_title({
                current_folder_ascendant_hierarchy: [
                    {
                        id: 2,
                        title: "folder A",
                        owner: {
                            id: 101,
                        },
                        last_update_date: "2018-08-07T16:42:49+02:00",
                    },
                    {
                        id: 3,
                        title: "Current folder",
                        owner: {
                            id: 101,
                            display_name: "user (login)",
                        },
                        last_update_date: "2018-08-21T17:01:49+02:00",
                    },
                ],
                root_title: "Documents",
            });

            expect(title).toBe("Current folder");
        });

        it("returns the root title if the ascendant hierarchy is empty", () => {
            const title = getters.current_folder_title({
                current_folder_ascendant_hierarchy: [],
                root_title: "Documents",
            });

            expect(title).toBe("Documents");
        });
    });

    describe("global_upload_progress", () => {
        it("returns the global upload progress by computing the mean of all progress values", () => {
            const global_progress = getters.global_upload_progress({
                folder_content: [
                    { id: 1 },
                    { id: 2, progress: 25, upload_error: "Error during upload" },
                    { id: 3, progress: 25, upload_error: null },
                    { id: 4 },
                    { id: 5, progress: 75, upload_error: null },
                    { id: 6 },
                ],
            });

            expect(global_progress).toEqual(50);
        });

        it("returns 0 if no upload is in progress", () => {
            const global_progress = getters.global_upload_progress({
                folder_content: [{ id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }, { id: 6 }],
            });

            expect(global_progress).toEqual(0);
        });
    });
});
