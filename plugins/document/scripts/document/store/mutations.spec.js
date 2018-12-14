/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import * as mutations from "./mutations.js";

describe("Store mutations", () => {
    describe("beginLoading()", () => {
        it("sets loading to true", () => {
            const state = {
                is_loading_folder: false
            };

            mutations.beginLoading(state);

            expect(state.is_loading_folder).toBe(true);
        });
    });

    describe("stopLoading()", () => {
        it("sets loading to false", () => {
            const state = {
                is_loading_folder: true
            };

            mutations.stopLoading(state);

            expect(state.is_loading_folder).toBe(false);
        });
    });

    describe("resetErrors()", () => {
        it("resets all errors", () => {
            const state = {
                has_folder_permission_error: true,
                has_folder_loading_error: true,
                folder_loading_error: "Not found"
            };

            mutations.resetErrors(state);

            expect(state.has_folder_permission_error).toBe(false);
            expect(state.has_folder_loading_error).toBe(false);
            expect(state.folder_loading_error).toBeNull();
        });
    });

    describe("foldFolderContent", () => {
        /**
         *         Folder structure
         *
         *              __0__
         *             /     \
         *           _30      31
         *          /   \
         *        _32    33-
         *       /   \   / \ \
         *    _34    35 40 41 42
         *   /  \   / \     \
         *  36  37 38 39     43
         */
        it("stores the ids of the items to hide and updates which folders do fold which items.", () => {
            const state = {
                folded_by_map: {},
                folded_items_ids: [],
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
                    { id: 43, parent_id: 41 },
                    { id: 42, parent_id: 33 },
                    { id: 31, parent_id: 0 }
                ]
            };

            mutations.foldFolderContent(state, 35);
            expect(state.folded_items_ids).toEqual([38, 39]);
            expect(state.folded_by_map).toEqual({
                "35": [38, 39]
            });

            mutations.foldFolderContent(state, 34);
            expect(state.folded_items_ids).toEqual([38, 39, 36, 37]);
            expect(state.folded_by_map).toEqual({
                "35": [38, 39],
                "34": [36, 37]
            });

            mutations.foldFolderContent(state, 32);
            expect(state.folded_items_ids).toEqual([38, 39, 36, 37, 34, 35]);
            expect(state.folded_by_map).toEqual({
                "35": [38, 39],
                "34": [36, 37],
                "32": [34, 35]
            });
        });
    });

    describe("unfoldFolderContent", () => {
        it("remove all the ids of the children and grand children of a given from state.folded_items_ids.", () => {
            const state = {
                folded_by_map: {
                    "32": [34, 35],
                    "34": [36, 37],
                    "35": [38, 39]
                },
                folded_items_ids: [34, 36, 37, 35, 38, 39],
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
                    { id: 43, parent_id: 41 },
                    { id: 42, parent_id: 33 },
                    { id: 31, parent_id: 0 }
                ]
            };

            mutations.unfoldFolderContent(state, 32);
            expect(state.folded_items_ids).toEqual([36, 37, 38, 39]);
            expect(state.folded_by_map).toEqual({
                "34": [36, 37],
                "35": [38, 39]
            });

            mutations.unfoldFolderContent(state, 34);
            expect(state.folded_items_ids).toEqual([38, 39]);
            expect(state.folded_by_map).toEqual({
                "35": [38, 39]
            });

            mutations.unfoldFolderContent(state, 35);
            expect(state.folded_items_ids).toEqual([]);
            expect(state.folded_by_map).toEqual({});
        });
    });
});
