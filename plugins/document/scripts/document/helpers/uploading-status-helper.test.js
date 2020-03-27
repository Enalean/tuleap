/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import {
    isItemInTreeViewWithoutUpload,
    isItemUploadingInQuickLookMode,
    isItemUploadingInTreeView,
    hasNoUploadingContent,
} from "./uploading-status-helper.js";

describe("FolderContentRow", () => {
    describe("hasNoUploadingContent", () => {
        it(`Item is not uploading
            When item has no on going upload`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: false,
                is_uploading: false,
                is_uploading_new_version: false,
            };

            expect(hasNoUploadingContent(item)).toBe(true);
        });

        it(`Item is uploading
            When item is uploading in a collapsed folder`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: true,
                is_uploading: false,
                is_uploading_new_version: false,
            };

            expect(hasNoUploadingContent(item)).toBe(false);
        });

        it(`Item is uploading
            When item is uploading`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: false,
                is_uploading: true,
                is_uploading_new_version: false,
            };

            expect(hasNoUploadingContent(item)).toBe(false);
        });

        it(`Item is uploading
            When item has a new version uploading`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: false,
                is_uploading: false,
                is_uploading_new_version: true,
            };

            expect(hasNoUploadingContent(item)).toBe(false);
        });
    });

    describe("is_item_uploading_in_quick look_mode", () => {
        it(`Item is uploading in quick look mode
            When item is uploading in a collapsed folder in quick look mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: true,
                is_uploading_new_version: false,
            };

            const quick_look_mode = true;

            expect(isItemUploadingInQuickLookMode(item, quick_look_mode)).toBe(true);
        });

        it(`Item is uploading in quick look mode
            When item has a new version uploading in quick look mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: true,
            };

            const quick_look_mode = true;

            expect(isItemUploadingInQuickLookMode(item, quick_look_mode)).toBe(true);
        });

        it(`Item is not uploading in quick look mode
            When user is on tree view mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
            };

            const quick_look_mode = false;

            expect(isItemUploadingInQuickLookMode(item, quick_look_mode)).toBe(false);
        });
    });

    describe("isItemUploadingInTreeView", () => {
        it(`Item is uploading in tree view mode
            When item is uploading in a collapsed folder in tree view mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: true,
                is_uploading_new_version: false,
            };

            const quick_look_mode = false;

            expect(isItemUploadingInTreeView(item, quick_look_mode)).toBe(true);
        });

        it(`Item is uploading in tree view mode
            When item has a new version uploading in tree view mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: true,
            };

            const quick_look_mode = false;

            expect(isItemUploadingInTreeView(item, quick_look_mode)).toBe(true);
        });

        it(`Item is not uploading in tree view mode
            When item is uploading in a collapsed folder in quick look mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
            };

            const quick_look_mode = true;

            expect(isItemUploadingInTreeView(item, quick_look_mode)).toBe(false);
        });
    });

    describe("isItemInTreeViewWithoutUpload", () => {
        it(`Item is not in tree view
            When it is in quick look mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
            };

            const quick_look_mode = true;

            expect(isItemInTreeViewWithoutUpload(item, quick_look_mode)).toBe(false);
        });

        it(`Item has an on going upload
            When item has an ongoing upload in tree view mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_new_version: true,
            };

            const quick_look_mode = false;

            expect(isItemInTreeViewWithoutUpload(item, quick_look_mode)).toBe(false);
        });

        it(`Item has an on going version upload
            When item has an on going version upload in quick look mode`, () => {
            const item = {
                id: 1,
                title: "my item title",
                type: "file",
                is_uploading_new_version: true,
            };

            const quick_look_mode = true;

            expect(isItemInTreeViewWithoutUpload(item, quick_look_mode)).toBe(false);
        });
    });
});
