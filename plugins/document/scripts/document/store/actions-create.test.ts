/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
import {
    addNewUploadFile,
    createNewItem,
    adjustItemToContentAfterItemCreationInAFolder,
} from "./actions-create";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { TYPE_FILE } from "../constants";
import * as upload_file from "./actions-helpers/upload-file";
import type { ActionContext } from "vuex";
import type { CreatedItem, FakeItem, Folder, Item, ItemFile, RootState, State } from "../type";
import type { ConfigurationState } from "./configuration";
import type { Upload } from "tus-js-client";
import emitter from "../helpers/emitter";
import * as flag_item_as_created from "./actions-helpers/flag-item-as-created";
import { buildFakeItem } from "../helpers/item-builder";

jest.mock("../helpers/emitter");

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
        jest.clearAllMocks();
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
            const fake_item = buildFakeItem();

            getItem.mockResolvedValue(item);

            await createNewItem(context, [item, parent, current_folder, fake_item]);

            expect(addNewEmpty).toHaveBeenCalledWith(correct_item, parent.id);
        });

        it("Creates new document warns about new item creation and reload folder content", async () => {
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
            const fake_item = buildFakeItem();
            getItem.mockResolvedValue(item);

            await createNewItem(context, [item, parent, current_folder, fake_item]);

            expect(getItem).toHaveBeenCalledWith(66);
            expect(emitter.emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 66 });
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
            const fake_item = buildFakeItem();

            await createNewItem(context, [item, parent, current_folder, fake_item]);

            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expect.any(Object),
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
            const fake_item = buildFakeItem();

            await createNewItem(context, [item, folder_of_created_item, current_folder, fake_item]);

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
            const fake_item = buildFakeItem();

            await createNewItem(context, [
                item,
                collapsed_folder_of_created_item,
                current_folder,
                fake_item,
            ]);
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
            const fake_item = buildFakeItem();

            await createNewItem(context, [
                item,
                collapsed_folder_of_created_item,
                current_folder,
                fake_item,
            ]);
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
            const fake_item = buildFakeItem();

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
                approval_table: null,
                has_approval_table: false,
                is_approval_table_enabled: false,
            } as FakeItem;

            await createNewItem(context, [item, folder_of_created_item, current_folder, fake_item]);

            expect(uploadFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader,
            );
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                folder_of_created_item,
                expected_fake_item_with_uploader,
                true,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader,
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
            const fake_item = buildFakeItem();

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
                approval_table: null,
                has_approval_table: false,
                is_approval_table_enabled: false,
            } as FakeItem;

            await createNewItem(context, [
                item,
                collapsed_folder_of_created_item,
                current_folder,
                fake_item,
            ]);

            expect(uploadFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader,
            );
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                collapsed_folder_of_created_item,
                expected_fake_item_with_uploader,
                false,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader,
            );
            expect(context.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                { collapsed_folder: collapsed_folder_of_created_item, toggle: true },
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
            const fake_item = buildFakeItem();

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
                approval_table: null,
                has_approval_table: false,
                is_approval_table_enabled: false,
            } as FakeItem;

            await createNewItem(context, [
                item,
                extended_folder_of_created_item,
                current_folder,
                fake_item,
            ]);

            expect(uploadFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader,
            );
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                extended_folder_of_created_item,
                expected_fake_item_with_uploader,
                true,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader,
            );
            expect(context.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                { collapsed_folder: extended_folder_of_created_item, toggle: false },
            );
        });
    });

    describe("addNewUploadFile", () => {
        it("Creates a fake item with created item reference", async () => {
            context.state.folder_content = [{ id: 45 } as Folder];
            const dropped_file = { name: "filename.txt", size: 10, type: "text/plain" } as File;
            const parent = { id: 42 } as Folder;
            const fake_item = buildFakeItem();

            const created_item_reference = { id: 66 } as CreatedItem;
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference),
            );
            const uploader = {} as Upload;
            jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            await addNewUploadFile(context, [
                dropped_file,
                parent,
                "filename.txt",
                "",
                true,
                fake_item,
            ]);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 42,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
                approval_table: null,
                has_approval_table: false,
                is_approval_table_enabled: false,
            };
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader,
            );
        });
        it("Starts upload", async () => {
            context.state.folder_content = [{ id: 45 } as Folder];
            const dropped_file = { name: "filename.txt", size: 10, type: "text/plain" } as File;
            const parent = { id: 42 } as Folder;

            const created_item_reference = { id: 66 } as CreatedItem;
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference),
            );
            const uploader = {} as Upload;
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);
            const fake_item = buildFakeItem();

            await addNewUploadFile(context, [
                dropped_file,
                parent,
                "filename.txt",
                "",
                true,
                fake_item,
            ]);

            const expected_fake_item = {
                id: 66,
                title: "filename.txt",
                parent_id: 42,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
                approval_table: null,
                has_approval_table: false,
                is_approval_table_enabled: false,
            };
            expect(uploadFile).toHaveBeenCalledWith(
                context,
                dropped_file,
                expected_fake_item,
                created_item_reference,
                parent,
            );
        });
        it("Does not start upload nor create fake item if item reference already exist in the store", async () => {
            context.state.folder_content = [{ id: 45 } as Folder, { id: 66 } as ItemFile];
            const dropped_file = { name: "filename.txt", size: 10, type: "text/plain" } as File;
            const parent = { id: 42 } as Folder;
            const fake_item = buildFakeItem();

            const created_item_reference = { id: 66 } as CreatedItem;
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference),
            );
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockImplementation();

            await addNewUploadFile(context, [
                dropped_file,
                parent,
                "filename.txt",
                "",
                true,
                fake_item,
            ]);

            expect(context.commit).not.toHaveBeenCalled();
            expect(uploadFile).not.toHaveBeenCalled();
        });
        it("does not start upload if file is empty", async () => {
            context.state.folder_content = [{ id: 45 } as Folder];
            const dropped_file = { name: "empty-file.txt", size: 0, type: "text/plain" } as File;
            const parent = { id: 42 } as Folder;
            const fake_item = buildFakeItem();

            const created_item_reference = { id: 66 } as CreatedItem;
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference),
            );

            const created_item = { id: 66, parent_id: 42, type: "file" } as ItemFile;
            jest.spyOn(rest_querier, "getItem").mockResolvedValue(created_item);

            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockImplementation();

            await addNewUploadFile(context, [
                dropped_file,
                parent,
                "filename.txt",
                "",
                true,
                fake_item,
            ]);

            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                created_item,
            );
            expect(uploadFile).not.toHaveBeenCalled();
        });
    });

    describe("adjustItemToContentAfterItemCreationInAFolder", () => {
        let context: ActionContext<State, State>,
            flagItemAsCreated: jest.SpyInstance,
            getItem: jest.SpyInstance;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
                state: {} as State,
            } as unknown as ActionContext<State, State>;

            flagItemAsCreated = jest.spyOn(flag_item_as_created, "flagItemAsCreated");

            getItem = jest.spyOn(rest_querier, "getItem");
        });

        it("Item is added to folded content when we are adding content into tree view collapsed folder", async () => {
            const created_item = {
                id: 10,
                title: "folder",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-10-03T11:16:11+02:00",
            } as Folder;

            const parent = {
                id: 10,
                is_expanded: false,
            } as Folder;

            const current_folder = {
                id: 1,
            } as Folder;

            const item_id = 10;

            getItem.mockReturnValue(created_item);

            await adjustItemToContentAfterItemCreationInAFolder(context, {
                parent,
                current_folder,
                item_id,
            });

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                created_item,
            );
            expect(flagItemAsCreated).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                parent,
                created_item,
                false,
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                created_item,
            );
        });

        it("Item must not be added to folded content when parent is expanded", async () => {
            const created_item = {
                id: 10,
                title: "folder",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-10-03T11:16:11+02:00",
            } as Folder;

            const parent = {
                id: 10,
                is_expanded: true,
            } as Folder;

            const current_folder = {
                id: 1,
            } as Folder;

            const item_id = 10;

            getItem.mockReturnValue(created_item);

            await adjustItemToContentAfterItemCreationInAFolder(context, {
                parent,
                current_folder,
                item_id,
            });

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                created_item,
            );
            expect(flagItemAsCreated).toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                created_item,
            );
        });

        it("Item is not added to folded content when we are adding item in current folder", async () => {
            const created_item = {
                id: 10,
                title: "folder",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-10-03T11:16:11+02:00",
            } as Folder;

            const parent = {
                id: 10,
                is_expanded: true,
            } as Folder;

            const current_folder = {
                id: 1,
            } as Folder;

            const item_id = 10;

            getItem.mockReturnValue(created_item);

            await adjustItemToContentAfterItemCreationInAFolder(context, {
                parent,
                current_folder,
                item_id,
            });

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                created_item,
            );
            expect(flagItemAsCreated).toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                created_item,
            );
        });
    });
});
