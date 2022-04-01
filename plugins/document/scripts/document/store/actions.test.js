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

import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK } from "../constants";
import {
    addNewUploadFile,
    cancelFileUpload,
    cancelFolderUpload,
    cancelVersionUpload,
    createNewVersionFromEmpty,
    getWikisReferencingSameWikiPage,
} from "./actions.js";
import * as rest_querier from "../api/rest-querier";

import * as upload_file from "./actions-helpers/upload-file";
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
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
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
                is_uploading_in_collapsed_folder: false,
                is_uploading_new_version: false,
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
});
