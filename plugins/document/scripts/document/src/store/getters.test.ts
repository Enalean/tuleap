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

import { describe, expect, it } from "vitest";
import * as getters from "./getters";
import type { FakeItem, Folder, Item, State } from "../type";

describe("Store getters", () => {
    describe("current_folder_title", () => {
        it("returns the title of the last item in the ascendant hierarchy", () => {
            const title = getters.current_folder_title({
                current_folder_ascendant_hierarchy: [
                    {
                        id: 2,
                        title: "folder A",
                        last_update_date: "2018-08-07T16:42:49+02:00",
                    } as Folder,
                    {
                        id: 3,
                        title: "Current folder",
                        last_update_date: "2018-08-21T17:01:49+02:00",
                    } as Folder,
                ],
                root_title: "Documents",
            } as State);

            expect(title).toBe("Current folder");
        });

        it("returns the root title if the ascendant hierarchy is empty", () => {
            const hierarchy: Array<Folder> = [];
            const title = getters.current_folder_title({
                current_folder_ascendant_hierarchy: hierarchy,
                root_title: "Documents",
            } as State);

            expect(title).toBe("Documents");
        });
    });

    describe("global_upload_progress", () => {
        it("returns the global upload progress by computing the mean of all progress values", () => {
            const global_progress = getters.global_upload_progress({
                folder_content: [
                    { id: 1 } as Item,
                    { id: 2, progress: 25, upload_error: "Error during upload" } as FakeItem,
                    { id: 3, progress: 25, upload_error: null } as FakeItem,
                    { id: 4 } as Item,
                    { id: 5, progress: 75, upload_error: null } as FakeItem,
                    { id: 6 } as Item,
                ],
            } as State);

            expect(global_progress).toBe(50);
        });

        it("returns 0 if no upload is in progress", () => {
            const global_progress = getters.global_upload_progress({
                folder_content: [
                    { id: 1 } as Item,
                    { id: 2 } as Item,
                    { id: 3 } as Item,
                    { id: 4 } as Item,
                    { id: 5 } as Item,
                    { id: 6 } as Item,
                ],
            } as State);

            expect(global_progress).toBe(0);
        });
    });
});
