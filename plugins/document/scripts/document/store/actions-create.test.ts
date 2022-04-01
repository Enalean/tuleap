/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import * as rest_querier from "../api/rest-querier";
import { createNewItem } from "./actions-create";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { TYPE_FILE } from "../constants";
import * as upload_file from "./actions-helpers/upload-file";
import type { ActionContext } from "vuex";
import type { CreatedItem, FakeItem, Folder, Item, RootState } from "../type";
import type { ConfigurationState } from "./configuration";
import type { Upload } from "tus-js-client";

describe("actions-create", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        const project_id = "101";
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            state: {
                configuration: { project_id } as ConfigurationState,
                current_folder_ascendant_hierarchy: [],
            } as unknown as RootState,
        } as unknown as ActionContext<RootState, RootState>;
    });

    describe("createNewItem", () => {
        let addNewEmpty: jest.SpyInstance, getItem: jest.SpyInstance;

        beforeEach(() => {
            addNewEmpty = jest.spyOn(rest_querier, "addNewEmpty");
            getItem = jest.spyOn(rest_querier, "getItem");
        });

        it("Replace the obsolescence date with null when date is permantent", async () => {
            const created_item_reference = { id: 66 } as CreatedItem;
            addNewEmpty.mockResolvedValue(created_item_reference);

            const item = {
                id: 66,
                title: "whatever",
                type: "empty",
                obsolescence_date: "",
            } as unknown as Item;
            const correct_item = {
                id: 66,
                title: "whatever",
                type: "empty",
                obsolescence_date: null,
            } as Item;
            const parent = {
                id: 2,
                title: "my folder",
                type: "folder",
                is_expanded: true,
            } as Folder;
            const current_folder = parent;

            getItem.mockResolvedValue(item);

            await createNewItem(context, [item, parent, current_folder]);

            expect(addNewEmpty).toHaveBeenCalledWith(correct_item, parent.id);
        });

        it("Creates new document and reload folder content", async () => {
            const created_item_reference = { id: 66 } as CreatedItem;
            addNewEmpty.mockResolvedValue(created_item_reference);

            const item = { id: 66, title: "whatever", type: "empty" } as Item;
            const parent = {
                id: 2,
                title: "my folder",
                type: "folder",
                is_expanded: true,
            } as Folder;
            const current_folder = parent;
            getItem.mockResolvedValue(item);

            await createNewItem(context, [item, parent, current_folder]);

            expect(getItem).toHaveBeenCalledWith(66);
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
            expect(context.dispatch).not.toHaveBeenCalledWith("error/handleErrorsForModal");
        });

        it("Stores error when document creation fail", async () => {
            const error_message = "`title` is required.";
            mockFetchError(addNewEmpty, {
                status: 400,
                error_json: {
                    error: {
                        message: error_message,
                    },
                },
            });
            const parent = {
                id: 2,
                title: "my folder",
                type: "folder",
                is_expanded: true,
            } as Folder;
            const current_folder = parent;
            const item = { id: 66, title: "", type: "empty" } as Item;

            await createNewItem(context, [item, parent, current_folder]);

            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expect.any(Object)
            );
            expect(context.dispatch).toHaveBeenCalledWith("error/handleErrorsForModal", Error());
        });

        it("displays the created item when it is created in the current folder", async () => {
            const created_item_reference = { id: 66 } as CreatedItem;
            addNewEmpty.mockResolvedValue(created_item_reference);

            const item = { id: 66, title: "whatever", type: "empty" } as Item;
            getItem.mockResolvedValue(item);

            const folder_of_created_item = { id: 10 } as Folder;
            const current_folder = { id: 10 } as Folder;

            await createNewItem(context, [item, folder_of_created_item, current_folder]);

            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("not displays the created item when it is created in a collapsed folder", async () => {
            const created_item_reference = { id: 66 } as CreatedItem;
            addNewEmpty.mockResolvedValue(created_item_reference);

            const item = { id: 66, title: "whatever", type: "empty" } as Item;
            getItem.mockResolvedValue(item);

            const current_folder = { id: 30 } as Folder;
            const collapsed_folder_of_created_item = {
                id: 10,
                parent_id: 30,
                is_expanded: false,
            } as Folder;

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                collapsed_folder_of_created_item,
                item,
                false,
            ]);
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("displays the created item when it is created in a expanded folder which is not the same as the current folder", async () => {
            const created_item_reference = { id: 66 } as CreatedItem;
            addNewEmpty.mockResolvedValue(created_item_reference);

            const item = { id: 66, title: "whatever", type: "empty" } as Item;
            getItem.mockResolvedValue(item);

            const current_folder = { id: 18 } as Folder;
            const collapsed_folder_of_created_item = {
                id: 10,
                parent_id: 30,
                is_expanded: true,
            } as Folder;

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);
            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("displays the created file when it is created in the current folder", async () => {
            context.state.folder_content = [{ id: 10 } as Folder];
            const created_item_reference = { id: 66 } as CreatedItem;

            jest.spyOn(rest_querier, "addNewFile").mockResolvedValue(created_item_reference);
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties },
                permissions_for_groups: [
                    { can_manage: [{ id: 166_4 }] },
                    { can_read: [{ id: 166_3 }] },
                    { can_write: [{ id: 166_5 }] },
                ],
            } as unknown as Item;

            getItem.mockResolvedValue(item);
            const folder_of_created_item = { id: 10 } as Folder;
            const current_folder = { id: 10 } as Folder;
            const uploader = {} as Upload;
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            const expected_fake_item_with_uploader: FakeItem = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
            };

            await createNewItem(context, [item, folder_of_created_item, current_folder]);

            expect(uploadFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader
            );
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                folder_of_created_item,
                expected_fake_item_with_uploader,
                true,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader
            );
        });
        it("not displays the created file when it is created in a collapsed folder and displays the progress bar along the folder", async () => {
            context.state.folder_content = [{ id: 10 } as Folder];
            const created_item_reference = { id: 66 } as CreatedItem;

            jest.spyOn(rest_querier, "addNewFile").mockResolvedValue(created_item_reference);
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties },
            } as unknown as Item;

            getItem.mockResolvedValue(item);
            const current_folder = { id: 30 } as Folder;
            const collapsed_folder_of_created_item = {
                id: 10,
                parent_id: 30,
                is_expanded: false,
            } as Folder;
            const uploader = {} as Upload;
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            const expected_fake_item_with_uploader: FakeItem = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
            };

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);

            expect(uploadFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader
            );
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                collapsed_folder_of_created_item,
                expected_fake_item_with_uploader,
                false,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader
            );
            expect(context.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                [collapsed_folder_of_created_item, true]
            );
        });
        it("displays the created file when it is created in a extanded sub folder and not displays the progress bar along the folder", async () => {
            context.state.folder_content = [{ id: 10 } as Folder];
            const created_item_reference = { id: 66 } as CreatedItem;

            jest.spyOn(rest_querier, "addNewFile").mockResolvedValue(created_item_reference);
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties },
            } as unknown as Item;

            getItem.mockResolvedValue(item);
            const current_folder = { id: 30 } as Folder;
            const extended_folder_of_created_item = {
                id: 10,
                parent_id: 30,
                is_expanded: true,
            } as Folder;
            const uploader = {} as Upload;
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            const expected_fake_item_with_uploader: FakeItem = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
            };

            await createNewItem(context, [item, extended_folder_of_created_item, current_folder]);

            expect(uploadFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader
            );
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                extended_folder_of_created_item,
                expected_fake_item_with_uploader,
                true,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader
            );
            expect(context.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                [extended_folder_of_created_item, false]
            );
        });
    });
});
