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

import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK } from "../constants";
import {
    addNewUploadFile,
    cancelFileUpload,
    cancelFolderUpload,
    cancelVersionUpload,
    createNewEmbeddedFileVersionFromModal,
    createNewFileVersion,
    createNewFileVersionFromModal,
    createNewItem,
    createNewLinkVersionFromModal,
    createNewVersionFromEmpty,
    createNewWikiVersionFromModal,
    getWikisReferencingSameWikiPage,
    loadDocument,
    loadDocumentWithAscendentHierarchy,
    loadFolder,
    loadRootFolder,
    toggleQuickLook,
} from "./actions.js";
import * as load_ascendant_hierarchy from "./actions-helpers/load-ascendant-hierarchy";
import * as load_folder_content from "./actions-helpers/load-folder-content";
import * as rest_querier from "../api/rest-querier";

import * as upload_file from "./actions-helpers/upload-file";
import * as error_handler from "./actions-helpers/handle-errors";
import * as action_error_handler from "./error/error-actions";

describe("Store actions", () => {
    let context;

    beforeEach(() => {
        const project_id = 101;
        context = {
            commit: jest.fn(),
            state: {
                configuration: { project_id },
                current_folder_ascendant_hierarchy: [],
            },
        };
    });

    describe("loadRootFolder()", () => {
        it("load document root and then load its own content", async () => {
            const root_item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            const service = {
                root_item,
            };

            const loadFolderContent = jest.spyOn(load_folder_content, "loadFolderContent");
            jest.spyOn(rest_querier, "getDocumentManagerServiceInformation").mockReturnValue(
                service
            );
            jest.spyOn(rest_querier, "getFolderContent").mockReturnValue(root_item);
            const handle_error = jest.spyOn(error_handler, "handleErrors");

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoading");
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", root_item);
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
            expect(handle_error).not.toHaveBeenCalled();
            expect(loadFolderContent).toHaveBeenCalled();
            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2]
            ).resolves.toEqual(root_item);
        });

        it("When the user does not have access to the project, an error will be raised", async () => {
            mockFetchError(jest.spyOn(rest_querier, "getDocumentManagerServiceInformation"), {
                status: 403,
                error_json: {
                    error: {
                        message: "User can't access project",
                    },
                },
            });

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith("error/switchFolderPermissionError");
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });

        it("When the project can't be found, an error will be raised", async () => {
            const error_message = "Project does not exist.";
            mockFetchError(jest.spyOn(rest_querier, "getDocumentManagerServiceInformation"), {
                status: 404,
                error_json: {
                    error: {
                        message: error_message,
                    },
                },
            });

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith(
                "error/setFolderLoadingError",
                error_message
            );
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });

        it("When an error occurred, then the translated exception will be raised", async () => {
            const error_message = "My translated exception";
            mockFetchError(jest.spyOn(rest_querier, "getDocumentManagerServiceInformation"), {
                status: 404,
                error_json: {
                    error: {
                        i18n_error_message: error_message,
                    },
                },
            });

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith(
                "error/setFolderLoadingError",
                error_message
            );
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });
    });

    describe("loadFolder", () => {
        let getItem, loadFolderContent, loadAscendantHierarchy;

        beforeEach(() => {
            getItem = jest.spyOn(rest_querier, "getItem");
            loadFolderContent = jest
                .spyOn(load_folder_content, "loadFolderContent")
                .mockImplementation(() => {});
            loadAscendantHierarchy = jest
                .spyOn(load_ascendant_hierarchy, "loadAscendantHierarchy")
                .mockImplementation(() => {});
        });

        it("loads ascendant hierarchy and content for stored current folder", async () => {
            const current_folder = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            context.state.current_folder = current_folder;

            await loadFolder(context, 3);

            expect(getItem).not.toHaveBeenCalled();
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();

            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2]
            ).resolves.toEqual(current_folder);
            await expect(
                loadAscendantHierarchy.mock.calls[loadAscendantHierarchy.mock.calls.length - 1][2]
            ).resolves.toEqual(current_folder);
        });

        it("gets item if there isn't any current folder in the store", async () => {
            const folder_to_fetch = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            getItem.mockReturnValue(Promise.resolve(folder_to_fetch));

            await loadFolder(context, 3);

            expect(getItem).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_to_fetch);
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2]
            ).resolves.toEqual(folder_to_fetch);
            await expect(
                loadAscendantHierarchy.mock.calls[loadAscendantHierarchy.mock.calls.length - 1][2]
            ).resolves.toEqual(folder_to_fetch);
        });

        it("gets item when the requested folder is not in the store", async () => {
            context.state.current_folder = {
                id: 1,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            const folder_to_fetch = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            getItem.mockReturnValue(Promise.resolve(folder_to_fetch));

            await loadFolder(context, 3);

            expect(getItem).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_to_fetch);
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2]
            ).resolves.toEqual(folder_to_fetch);
            await expect(
                loadAscendantHierarchy.mock.calls[loadAscendantHierarchy.mock.calls.length - 1][2]
            ).resolves.toEqual(folder_to_fetch);
        });

        it("does not load ascendant hierarchy if folder is already inside the current one", async () => {
            const folder_a = {
                id: 2,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            };
            const folder_b = {
                id: 3,
                title: "folder B",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            };

            context.state.current_folder_ascendant_hierarchy = [folder_a, folder_b];
            context.state.current_folder = folder_a;

            await loadFolder(context, 2);

            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("saveAscendantHierarchy", [folder_a]);
            expect(context.commit).not.toHaveBeenCalledWith("setCurrentFolder");
        });

        it("overrides the stored current folder with the one found in ascendant hierarchy", async () => {
            const folder_a = {
                id: 2,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            };
            const folder_b = {
                id: 3,
                title: "folder B",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            };

            context.state.current_folder_ascendant_hierarchy = [folder_a, folder_b];
            context.state.current_folder = folder_b;

            await loadFolder(context, 2);

            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("saveAscendantHierarchy", [folder_a]);
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_a);
        });

        it("does not override the stored current folder with the one found in ascendant hierarchy if they are the same", async () => {
            const folder_a = {
                id: 2,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            };
            const folder_b = {
                id: 3,
                title: "folder B",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            };

            context.state.current_folder_ascendant_hierarchy = [folder_a, folder_b];
            context.state.current_folder = folder_a;

            await loadFolder(context, 2);

            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("saveAscendantHierarchy", [folder_a]);
            expect(context.commit).not.toHaveBeenCalledWith("setCurrentFolder", folder_a);
        });
    });

    describe("createNewItem", () => {
        let addNewEmpty, getItem;

        beforeEach(() => {
            addNewEmpty = jest.spyOn(rest_querier, "addNewEmpty");
            getItem = jest.spyOn(rest_querier, "getItem");
        });

        it("Replace the obsolescence date with null when date is permantent", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.mockReturnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty", obsolescence_date: "" };
            const correct_item = {
                id: 66,
                title: "whatever",
                type: "empty",
                obsolescence_date: null,
            };
            const parent = { id: 2, title: "my folder", type: "folder", is_expanded: true };
            const current_folder = parent;

            getItem.mockReturnValue(Promise.resolve(item));

            await createNewItem(context, [item, parent, current_folder]);

            expect(addNewEmpty).toHaveBeenCalledWith(correct_item, parent.id);
        });

        it("Creates new document and reload folder content", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.mockReturnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            const parent = { id: 2, title: "my folder", type: "folder", is_expanded: true };
            const current_folder = parent;
            getItem.mockReturnValue(Promise.resolve(item));

            await createNewItem(context, [item, parent, current_folder]);

            expect(getItem).toHaveBeenCalledWith(66);
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
            expect(context.commit).not.toHaveBeenCalledWith("error/setModalError");
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
            const parent = { id: 2, title: "my folder", type: "folder", is_expanded: true };
            const current_folder = parent;
            const item = { id: 66, title: "", type: "empty" };

            await createNewItem(context, [item, parent, current_folder]);

            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expect.any(Object)
            );
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", error_message);
        });

        it("displays the created item when it is created in the current folder", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.mockReturnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            getItem.mockReturnValue(Promise.resolve(item));

            const folder_of_created_item = { id: 10 };
            const current_folder = { id: 10 };

            await createNewItem(context, [item, folder_of_created_item, current_folder]);

            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("not displays the created item when it is created in a collapsed folder", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.mockReturnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            getItem.mockReturnValue(Promise.resolve(item));

            const current_folder = { id: 30 };
            const collapsed_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: false };

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                collapsed_folder_of_created_item,
                item,
                false,
            ]);
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("displays the created item when it is created in a expanded folder which is not the same as the current folder", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.mockReturnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            getItem.mockReturnValue(Promise.resolve(item));

            const current_folder = { id: 18 };
            const collapsed_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: true };

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);
            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("displays the created file when it is created in the current folder", async () => {
            context.state.folder_content = [{ id: 10 }];
            const created_item_reference = { id: 66 };

            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );
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
            };

            getItem.mockReturnValue(Promise.resolve(item));
            const folder_of_created_item = { id: 10 };
            const current_folder = { id: 10 };
            const uploader = {};
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
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
            context.state.folder_content = [{ id: 10 }];
            const created_item_reference = { id: 66 };

            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties },
            };

            getItem.mockReturnValue(Promise.resolve(item));
            const current_folder = { id: 30 };
            const collapsed_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: false };
            const uploader = {};
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
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
            context.state.folder_content = [{ id: 10 }];
            const created_item_reference = { id: 66 };

            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties },
            };

            getItem.mockReturnValue(Promise.resolve(item));
            const current_folder = { id: 30 };
            const extended_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: true };
            const uploader = {};
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null,
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

    describe("addNewUploadFile", () => {
        it("Creates a fake item with created item reference", async () => {
            context.state.folder_content = [{ id: 45 }];
            const dropped_file = { name: "filename.txt", size: 10, type: "text/plain" };
            const parent = { id: 42 };

            const created_item_reference = { id: 66 };
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );
            const uploader = {};
            jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            await addNewUploadFile(context, [dropped_file, parent, "filename.txt", "", true]);

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
            };
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                expected_fake_item_with_uploader
            );
        });
        it("Starts upload", async () => {
            context.state.folder_content = [{ id: 45 }];
            const dropped_file = { name: "filename.txt", size: 10, type: "text/plain" };
            const parent = { id: 42 };

            const created_item_reference = { id: 66 };
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );
            const uploader = {};
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockReturnValue(uploader);

            await addNewUploadFile(context, [dropped_file, parent, "filename.txt", "", true]);

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
            };
            expect(uploadFile).toHaveBeenCalledWith(
                context,
                dropped_file,
                expected_fake_item,
                created_item_reference,
                parent
            );
        });
        it("Does not start upload nor create fake item if item reference already exist in the store", async () => {
            context.state.folder_content = [{ id: 45 }, { id: 66 }];
            const dropped_file = { name: "filename.txt", size: 10, type: "text/plain" };
            const parent = { id: 42 };

            const created_item_reference = { id: 66 };
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );
            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockImplementation();

            await addNewUploadFile(context, [dropped_file, parent, "filename.txt", "", true]);

            expect(context.commit).not.toHaveBeenCalled();
            expect(uploadFile).not.toHaveBeenCalled();
        });
        it("does not start upload if file is empty", async () => {
            context.state.folder_content = [{ id: 45 }];
            const dropped_file = { name: "empty-file.txt", size: 0, type: "text/plain" };
            const parent = { id: 42 };

            const created_item_reference = { id: 66 };
            jest.spyOn(rest_querier, "addNewFile").mockReturnValue(
                Promise.resolve(created_item_reference)
            );

            const created_item = { id: 66, parent_id: 42, type: "file" };
            jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(created_item));

            const uploadFile = jest.spyOn(upload_file, "uploadFile").mockImplementation();

            await addNewUploadFile(context, [dropped_file, parent, "filename.txt", "", true]);

            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                created_item
            );
            expect(uploadFile).not.toHaveBeenCalled();
        });
    });
    describe("cancelFileUpload", () => {
        let item;
        beforeEach(() => {
            item = {
                uploader: {
                    abort: jest.fn(),
                },
            };
        });

        it("asks to tus client to abort the upload", async () => {
            await cancelFileUpload(context, item);
            expect(item.uploader.abort).toHaveBeenCalled();
        });
        it("asks to tus server to abort the upload, because tus client does not do it for us", async () => {
            const cancelUpload = jest.spyOn(rest_querier, "cancelUpload").mockImplementation();
            await cancelFileUpload(context, item);
            expect(cancelUpload).toHaveBeenCalledWith(item);
        });
        it("remove item from the store", async () => {
            await cancelFileUpload(context, item);
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
        });
        it("remove item from the store even if there is an error on cancelUpload", async () => {
            jest.spyOn(rest_querier, "cancelUpload").mockImplementation(() => {
                throw new Error("Failed to fetch");
            });
            await cancelFileUpload(context, item);
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
        });
    });

    describe("cancelVersionUpload", () => {
        let item;
        beforeEach(() => {
            item = {
                uploader: {
                    abort: jest.fn(),
                },
            };
        });

        it("asks to tus client to abort the upload", async () => {
            await cancelVersionUpload(context, item);
            expect(item.uploader.abort).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("removeVersionUploadProgress", item);
        });
    });
    describe("createNewFileVersion", () => {
        let createNewVersion, uploadVersion;

        beforeEach(() => {
            createNewVersion = jest.spyOn(rest_querier, "createNewVersion");
            uploadVersion = jest.spyOn(upload_file, "uploadVersion");
        });

        it("does not trigger any upload if the file is empty", async () => {
            const dropped_file = { name: "filename.txt", size: 0, type: "text/plain" };
            const item = {};

            createNewVersion.mockReturnValue(Promise.resolve());

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).not.toHaveBeenCalled();
        });

        it("uploads a new version of the file and releases the edition lock", async () => {
            const item = {
                id: 45,
                lock_info: null,
                title: "Electronic document management for dummies.pdf",
            };
            const NO_LOCK = false;

            context.state.folder_content = [{ id: 45 }];
            const dropped_file = { name: "filename.txt", size: 123, type: "text/plain" };

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.mockReturnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.mockReturnValue(uploader);

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).toHaveBeenCalled();
            expect(createNewVersion).toHaveBeenCalledWith(
                item,
                "Electronic document management for dummies.pdf",
                "",
                dropped_file,
                NO_LOCK,
                undefined
            );
        });
    });
    describe("createNewFileVersionFromModal", () => {
        let createNewVersion, uploadVersion;

        beforeEach(() => {
            createNewVersion = jest.spyOn(rest_querier, "createNewVersion");
            uploadVersion = jest.spyOn(upload_file, "uploadVersion");
        });

        it("uploads a new version of a file", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const updated_file = { name: "filename.txt", size: 123, type: "text/plain" };

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.mockReturnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.mockReturnValue(uploader);

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            await createNewFileVersionFromModal(context, [
                item,
                updated_file,
                version_title,
                version_changelog,
                is_version_locked,
            ]);

            expect(createNewVersion).toHaveBeenCalled();
            expect(uploadVersion).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the version creation", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const update_fail = {};

            createNewVersion.mockImplementation(() => {
                throw new Error("An error occurred");
            });

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";

            const promise_create_new_file_version = createNewFileVersionFromModal(context, [
                item,
                update_fail,
                version_title,
                version_changelog,
            ]);
            await expect(promise_create_new_file_version).rejects.toBeDefined();
            expect(createNewVersion).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", expect.anything());
            expect(uploadVersion).not.toHaveBeenCalled();
        });

        it("throws an error when there is an error 400 with the version creation", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const update_fail = {};

            mockFetchError(createNewVersion, {
                status: 400,
            });

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";

            await createNewFileVersionFromModal(context, [
                item,
                update_fail,
                version_title,
                version_changelog,
            ]);

            expect(createNewVersion).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "error/setModalError",
                "Internal server error"
            );
            expect(uploadVersion).not.toHaveBeenCalled();
        });
    });

    describe("createNewEmbeddedFileVersionFromModal", () => {
        let postEmbeddedFile;

        beforeEach(() => {
            postEmbeddedFile = jest
                .spyOn(rest_querier, "postEmbeddedFile")
                .mockImplementation(() => {});
        });

        it("updates an embedded file", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const new_html_content = { content: "<h1>Hello world!</h1>}}" };

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            await createNewEmbeddedFileVersionFromModal(context, [
                item,
                new_html_content,
                version_title,
                version_changelog,
                is_version_locked,
            ]);

            expect(postEmbeddedFile).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const new_html_content = { content: "<h1>Hello world!</h1>}}" };

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            postEmbeddedFile.mockImplementation(() => {
                throw new Error("nope");
            });

            const promise_new_embedded_file = createNewEmbeddedFileVersionFromModal(context, [
                item,
                new_html_content,
                version_title,
                version_changelog,
                is_version_locked,
            ]);
            await expect(promise_new_embedded_file).rejects.toBeDefined();
            expect(postEmbeddedFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", expect.anything());
        });
    });

    describe("createNewWikiVersionFromModal", () => {
        let postWiki;

        beforeEach(() => {
            postWiki = jest.spyOn(rest_querier, "postWiki").mockImplementation(() => {});
        });

        it("updates a wiki page name", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const page_name = "kinky wiki";

            const version_title = "NSFW";
            const version_changelog = "Changed title to NSFW";
            const is_version_locked = true;

            await createNewWikiVersionFromModal(context, [
                item,
                page_name,
                version_title,
                version_changelog,
                is_version_locked,
            ]);

            expect(postWiki).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const page_name = "kinky wiki";

            const version_title = "NSFW";
            const version_changelog = "Changed title to NSFW";
            const is_version_locked = true;

            postWiki.mockImplementation(() => {
                throw new Error("nope");
            });

            const promise_create_new_wiki = createNewWikiVersionFromModal(context, [
                item,
                page_name,
                version_title,
                version_changelog,
                is_version_locked,
            ]);
            await expect(promise_create_new_wiki).rejects.toBeDefined();
            expect(postWiki).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", expect.anything());
        });
    });

    describe("createNewLinkVersionFromModal", () => {
        let postLinkVersion;

        beforeEach(() => {
            postLinkVersion = jest
                .spyOn(rest_querier, "postLinkVersion")
                .mockImplementation(() => {});
        });

        it("updates a link url", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const new_link_url = "https://moogle.fr";

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            await createNewLinkVersionFromModal(context, [
                item,
                new_link_url,
                version_title,
                version_changelog,
                is_version_locked,
            ]);

            expect(postLinkVersion).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const new_link_url = "https://moogle.fr";

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            postLinkVersion.mockImplementation(() => {
                throw new Error("nope");
            });

            const promise_new_link_version = createNewLinkVersionFromModal(context, [
                item,
                new_link_url,
                version_title,
                version_changelog,
                is_version_locked,
            ]);
            await expect(promise_new_link_version).rejects.toBeDefined();
            expect(postLinkVersion).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", expect.anything());
        });
    });

    describe("cancelFolderUpload", () => {
        let folder, item, context;

        beforeEach(() => {
            folder = {
                title: "My folder",
                id: 123,
            };

            item = {
                parent_id: folder.id,
                is_uploading_new_version: false,
                uploader: {
                    abort: jest.fn(),
                },
            };

            context = {
                commit: jest.fn(),
                state: {
                    files_uploads_list: [item],
                },
            };
        });

        it("should cancel the uploads of all the files being uploaded in the given folder.", async () => {
            await cancelFolderUpload(context, folder);

            expect(item.uploader.abort).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
            expect(context.commit).toHaveBeenCalledWith("removeFileFromUploadsList", item);

            expect(context.commit).toHaveBeenCalledWith("resetFolderIsUploading", folder);
        });

        it("should cancel the new version uploads of files being updated in the given folder.", async () => {
            item.is_uploading_new_version = true;

            await cancelFolderUpload(context, folder);

            expect(item.uploader.abort).toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalledWith("removeItemFromFolderContent", item);
            expect(context.commit).not.toHaveBeenCalledWith("removeFileFromUploadsList", item);

            expect(context.commit).toHaveBeenCalledWith("removeVersionUploadProgress", item);

            expect(context.commit).toHaveBeenCalledWith("resetFolderIsUploading", folder);
        });
    });

    describe("loadDocumentWithAscendentHierarchy", () => {
        let getItem, loadFolderContent, loadAscendantHierarchy;

        beforeEach(() => {
            getItem = jest.spyOn(rest_querier, "getItem");
            loadFolderContent = jest
                .spyOn(load_folder_content, "loadFolderContent")
                .mockImplementation(() => {});
            loadAscendantHierarchy = jest
                .spyOn(load_ascendant_hierarchy, "loadAscendantHierarchy")
                .mockImplementation(() => {});
        });

        it("loads ascendant hierarchy and content of item", async () => {
            const current_folder = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            const item = {
                id: 42,
                title: "My embedded file",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            context.state.current_folder = current_folder;

            getItem.mockReturnValue(Promise.resolve(item));
            loadFolderContent.mockReturnValue(Promise.resolve(current_folder));

            await loadDocumentWithAscendentHierarchy(42);
            expect(loadAscendantHierarchy).toHaveBeenCalled();
        });

        it("throw error if something went wrong", async () => {
            getItem.mockImplementation(() => {
                throw new Error("nope");
            });

            await expect(loadDocumentWithAscendentHierarchy(context, 42)).rejects.toBeDefined();
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "error/setItemLoadingError",
                "Internal server error"
            );
        });

        it("throw error permission error if user does not have enough permissions", async () => {
            mockFetchError(getItem, {
                status: 403,
            });

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith("error/switchItemPermissionError");
        });

        it("throw translated exceptions", async () => {
            mockFetchError(getItem, {
                status: 400,
                error_json: {
                    error: {
                        i18n_error_message: "My translated error",
                    },
                },
            });

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith(
                "error/setItemLoadingError",
                "My translated error"
            );
        });

        it("throw internal server error if something bad happens", async () => {
            mockFetchError(getItem, {
                status: 400,
                response: {},
            });

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith(
                "error/setItemLoadingError",
                "Internal server error"
            );
        });
    });

    describe("getWikisReferencingSameWikiPage()", () => {
        let getItemsReferencingSameWikiPage,
            getParents,
            context = {};

        beforeEach(() => {
            getItemsReferencingSameWikiPage = jest.spyOn(
                rest_querier,
                "getItemsReferencingSameWikiPage"
            );
            getParents = jest.spyOn(rest_querier, "getParents");
        });

        it("should return a collection of the items referencing the same wiki page", async () => {
            const wiki_1 = {
                item_name: "wiki 1",
                item_id: 1,
            };

            const wiki_2 = {
                item_name: "wiki 2",
                item_id: 2,
            };

            getItemsReferencingSameWikiPage.mockReturnValue([wiki_1, wiki_2]);

            getParents
                .mockReturnValueOnce(
                    Promise.resolve([
                        {
                            title: "Project documentation",
                        },
                    ])
                )
                .mockReturnValueOnce(
                    Promise.resolve([
                        {
                            title: "Project documentation",
                        },
                        {
                            title: "Folder 1",
                        },
                    ])
                );

            const target_wiki = {
                title: "wiki 3",
                wiki_properties: {
                    page_name: "A wiki page",
                    page_id: 123,
                },
            };

            const referencers = await getWikisReferencingSameWikiPage(context, target_wiki);

            expect(referencers).toEqual([
                {
                    path: "/Project documentation/wiki 1",
                    id: 1,
                },
                {
                    path: "/Project documentation/Folder 1/wiki 2",
                    id: 2,
                },
            ]);
        });

        it("should return null if there is a rest exception", async () => {
            const wiki_1 = {
                item_name: "wiki 1",
                item_id: 1,
            };

            const wiki_2 = {
                item_name: "wiki 2",
                item_id: 2,
            };

            getItemsReferencingSameWikiPage.mockReturnValue([wiki_1, wiki_2]);
            getParents.mockReturnValue(Promise.reject(500));

            const target_wiki = {
                title: "wiki 3",
                wiki_properties: {
                    page_name: "A wiki page",
                    page_id: 123,
                },
            };

            const referencers = await getWikisReferencingSameWikiPage(context, target_wiki);

            expect(referencers).toEqual(null);
        });
    });

    describe("toggleQuickLook", () => {
        let context;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
                state: {
                    folder_content: [{ id: 100, type: TYPE_FILE }],
                },
            };
        });

        it("should load item and store it as open in quick look", async () => {
            const item = {
                id: 123,
                title: "My file",
                type: TYPE_FILE,
                description: "n",
                owner: {
                    id: 102,
                },
                status: "none",
                obsolescence_date: null,
            };

            jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(item));

            await toggleQuickLook(context, item.id);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingCurrentlyPreviewedItem");
            expect(context.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
            expect(context.commit).toHaveBeenCalledWith("toggleQuickLook", true);
            expect(context.commit).toHaveBeenCalledWith("stopLoadingCurrentlyPreviewedItem");
        });
    });
    describe("createNewVersionFromEmpty -", () => {
        let context,
            postNewLinkVersionFromEmpty,
            postNewEmbeddedFileVersionFromEmpty,
            postNewFileVersionFromEmpty,
            handleErrorsForModal;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
                state: {
                    folder_content: [{ id: 123, type: TYPE_EMPTY }],
                },
            };

            postNewLinkVersionFromEmpty = jest.spyOn(rest_querier, "postNewLinkVersionFromEmpty");
            postNewEmbeddedFileVersionFromEmpty = jest
                .spyOn(rest_querier, "postNewEmbeddedFileVersionFromEmpty")
                .mockReturnValue(Promise.resolve());
            postNewFileVersionFromEmpty = jest.spyOn(rest_querier, "postNewFileVersionFromEmpty");
            handleErrorsForModal = jest.spyOn(action_error_handler, "handleErrorsForModal");
        });

        it("should update the empty document to link document", async () => {
            const item_to_update = {
                type: TYPE_EMPTY,
                link_properties: {
                    link_url: "https://example.test",
                },
            };
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            };

            const updated_item = {
                id: 123,
                type: TYPE_LINK,
            };
            jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(updated_item));
            postNewLinkVersionFromEmpty.mockReturnValue(Promise.resolve());

            await createNewVersionFromEmpty(context, [TYPE_LINK, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).toHaveBeenCalled();
            expect(postNewEmbeddedFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(handleErrorsForModal).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );

            expect(context.commit).toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });

        it("should update the empty document to embedded_file document", async () => {
            const item_to_update = {
                type: TYPE_EMPTY,
                embedded_properties: {
                    content: "content",
                },
            };
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            };

            const updated_item = {
                id: 123,
                type: TYPE_EMBEDDED,
            };

            jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(updated_item));
            postNewEmbeddedFileVersionFromEmpty.mockReturnValue(Promise.resolve());

            await createNewVersionFromEmpty(context, [TYPE_EMBEDDED, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewEmbeddedFileVersionFromEmpty).toHaveBeenCalled();
            expect(postNewFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(handleErrorsForModal).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );

            expect(context.commit).toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });

        it("should update the empty document to file document", async () => {
            const item_to_update = {
                type: TYPE_EMPTY,
                file_properties: {
                    file: "",
                },
            };
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            };

            const updated_item = {
                id: 123,
                type: TYPE_FILE,
            };
            const uploadVersionFromEmpty = jest
                .spyOn(upload_file, "uploadVersionFromEmpty")
                .mockReturnValue({});
            postNewFileVersionFromEmpty.mockReturnValue(Promise.resolve());
            jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(updated_item));

            await createNewVersionFromEmpty(context, [TYPE_FILE, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewEmbeddedFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewFileVersionFromEmpty).toHaveBeenCalled();
            expect(uploadVersionFromEmpty).toHaveBeenCalled();
            expect(handleErrorsForModal).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );

            expect(context.commit).toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });

        it("should failed the update", async () => {
            const item_to_update = {
                type: TYPE_EMPTY,
                link_properties: {
                    link_url: "https://example.test",
                },
            };
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            };

            const updated_item = {
                id: 123,
                type: TYPE_LINK,
            };

            const getItem = jest.spyOn(rest_querier, "getItem");
            postNewLinkVersionFromEmpty.mockImplementation(() => {
                throw new Error("Failed to update");
            });

            await expect(
                createNewVersionFromEmpty(context, [TYPE_LINK, item, item_to_update])
            ).rejects.toBeDefined();
            expect(postNewLinkVersionFromEmpty).toHaveBeenCalled();
            expect(handleErrorsForModal).toHaveBeenCalled();
            expect(getItem).not.toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );
            expect(context.commit).not.toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });
    });

    describe("loadDocument", () => {
        let getItem;

        it("loads an item", async () => {
            const item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            context.state.item = item;

            getItem = jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve());

            await loadDocument(context, 3);

            expect(getItem).toHaveBeenCalled();
        });

        it("handle error when document load fails", async () => {
            const item = {
                id: 3,
                title: "test",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };

            context.state.item = item;

            getItem = jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.reject("error"));

            await expect(loadDocument(context, 3)).rejects.toBeDefined();
            expect(getItem).toHaveBeenCalled();
        });
    });
});
