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

import * as mutations from "./mutations-global.js";

describe("Store mutations", () => {
    describe("beginLoading()", () => {
        it("sets loading to true", () => {
            const state = {
                is_loading_folder: false,
            };

            mutations.beginLoading(state);

            expect(state.is_loading_folder).toBe(true);
        });
    });

    describe("stopLoading()", () => {
        it("sets loading to false", () => {
            const state = {
                is_loading_folder: true,
            };

            mutations.stopLoading(state);

            expect(state.is_loading_folder).toBe(false);
        });
    });

    describe("appendFolderToAscendantHierarchy", () => {
        it("get all the ids of the direct ascendants", () => {
            const target_folder = { id: 43, parent_id: 41 };
            const state = {
                current_folder_ascendant_hierarchy: [],
                folder_content: [
                    { id: 30, parent_id: 0 },
                    { id: 32, parent_id: 30 },
                    { id: 34, parent_id: 32 },
                    { id: 36, parent_id: 34 },
                    { id: 37, parent_id: 34 },
                    { id: 35, parent_id: 32 },
                    { id: 38, parent_id: 35 },
                    { id: 39, parent_id: 35 },
                    { id: 33, parent_id: 30 },
                    { id: 40, parent_id: 33 },
                    { id: 41, parent_id: 33 },
                    target_folder,
                    { id: 42, parent_id: 33 },
                    { id: 31, parent_id: 0 },
                ],
            };

            mutations.appendFolderToAscendantHierarchy(state, target_folder);
            expect(state.current_folder_ascendant_hierarchy).toEqual([
                { id: 30, parent_id: 0 },
                { id: 33, parent_id: 30 },
                { id: 41, parent_id: 33 },
                target_folder,
            ]);
        });
    });

    it("store project user groups", () => {
        const state = {
            project_ugroups: null,
        };

        const retrieved_project_ugroups = [{ id: "102_3", label: "Project members" }];
        mutations.setProjectUserGroups(state, retrieved_project_ugroups);
        expect(state.project_ugroups).toEqual(retrieved_project_ugroups);
    });

    describe("toggle quick look", () => {
        it("toggle quick look to true", () => {
            const state = {
                toggle_quick_look: false,
            };
            mutations.toggleQuickLook(state, true);

            expect(state.toggle_quick_look).toEqual(true);
        });

        it("toggle quick look to false", () => {
            const state = {
                toggle_quick_look: true,
            };
            mutations.toggleQuickLook(state, false);

            expect(state.toggle_quick_look).toEqual(false);
        });
    });
});
