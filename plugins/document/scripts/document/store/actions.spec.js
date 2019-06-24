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

import { mockFetchError } from "tlp-mocks";
import {
    addNewUploadFile,
    cancelFileUpload,
    cancelFolderUpload,
    cancelVersionUpload,
    createNewItem,
    loadFolder,
    loadDocumentWithAscendentHierarchy,
    loadRootFolder,
    setUserPreferenciesForFolder,
    setUserPreferenciesForUI,
    unsetUnderConstructionUserPreference,
    createNewFileVersion,
    createNewFileVersionFromModal,
    createNewEmbeddedFileVersionFromModal,
    createNewWikiVersionFromModal,
    createNewLinkVersionFromModal,
    deleteItem,
    getWikisReferencingSameWikiPage,
    lockFile,
    unlockFile
} from "./actions.js";
import {
    restore as restoreUploadFile,
    restore as restoreUploadVersion,
    rewire$uploadFile,
    rewire$uploadVersion
} from "./actions-helpers/upload-file.js";
import {
    restore as restoreRestQuerier,
    rewire$addNewEmpty,
    rewire$addNewFile,
    rewire$cancelUpload,
    rewire$createNewVersion,
    rewire$deleteUserPreferenciesForFolderInProject,
    rewire$addUserLegacyUIPreferency,
    rewire$deleteUserPreferenciesForUnderConstructionModal,
    rewire$getItem,
    rewire$getProject,
    rewire$patchUserPreferenciesForFolderInProject,
    rewire$patchEmbeddedFile,
    rewire$patchWiki,
    rewire$patchLink,
    rewire$deleteFile,
    rewire$deleteLink,
    rewire$deleteEmbeddedFile,
    rewire$deleteWiki,
    rewire$deleteFolder,
    rewire$deleteEmptyDocument,
    rewire$getParents,
    rewire$getItemsReferencingSameWikiPage,
    rewire$postLockFile,
    rewire$deleteLockFile
} from "../api/rest-querier.js";
import {
    restore as restoreLoadFolderContent,
    rewire$loadFolderContent
} from "./actions-helpers/load-folder-content.js";
import {
    restore as restoreLoadAscendantHierarchy,
    rewire$loadAscendantHierarchy
} from "./actions-helpers/load-ascendant-hierarchy.js";

import {
    TYPE_FILE,
    TYPE_LINK,
    TYPE_EMBEDDED,
    TYPE_WIKI,
    TYPE_FOLDER,
    TYPE_EMPTY
} from "../constants.js";

describe("Store actions", () => {
    afterEach(() => {
        restoreRestQuerier();
        restoreLoadFolderContent();
        restoreLoadAscendantHierarchy();
        restoreUploadFile();
        restoreUploadVersion();
    });

    let context,
        getProject,
        getItem,
        loadFolderContent,
        loadAscendantHierarchy,
        deleteUserPreferenciesForFolderInProject,
        deleteUserPreferenciesForUnderConstructionModal,
        patchUserPreferenciesForFolderInProject,
        addUserLegacyUIPreferency,
        addNewEmpty,
        addNewFile,
        uploadFile,
        cancelUpload,
        createNewVersion,
        uploadVersion,
        patchEmbeddedFile,
        patchWiki,
        patchLink;

    beforeEach(() => {
        const project_id = 101;
        context = {
            commit: jasmine.createSpy("commit"),
            state: {
                project_id,
                current_folder_ascendant_hierarchy: []
            }
        };

        getProject = jasmine.createSpy("getProject");
        rewire$getProject(getProject);

        getItem = jasmine.createSpy("getItem");
        rewire$getItem(getItem);

        loadFolderContent = jasmine.createSpy("loadFolderContent");
        rewire$loadFolderContent(loadFolderContent);

        loadAscendantHierarchy = jasmine.createSpy("loadAscendantHierarchy");
        rewire$loadAscendantHierarchy(loadAscendantHierarchy);

        addNewEmpty = jasmine.createSpy("addNewEmpty");
        rewire$addNewEmpty(addNewEmpty);

        addNewFile = jasmine.createSpy("addNewFile");
        rewire$addNewFile(addNewFile);

        uploadFile = jasmine.createSpy("uploadFile");
        rewire$uploadFile(uploadFile);

        uploadVersion = jasmine.createSpy("uploadVersion");
        rewire$uploadVersion(uploadVersion);

        cancelUpload = jasmine.createSpy("cancelUpload");
        rewire$cancelUpload(cancelUpload);

        createNewVersion = jasmine.createSpy("createNewVersion");
        rewire$createNewVersion(createNewVersion);

        deleteUserPreferenciesForFolderInProject = jasmine.createSpy(
            "deleteUserPreferenciesForFolderInProject"
        );
        rewire$deleteUserPreferenciesForFolderInProject(deleteUserPreferenciesForFolderInProject);

        deleteUserPreferenciesForUnderConstructionModal = jasmine.createSpy(
            "deleteUserPreferenciesForUnderConstructionModal"
        );
        rewire$deleteUserPreferenciesForUnderConstructionModal(
            deleteUserPreferenciesForUnderConstructionModal
        );

        patchUserPreferenciesForFolderInProject = jasmine.createSpy(
            "patchUserPreferenciesForFolderInProject"
        );
        rewire$patchUserPreferenciesForFolderInProject(patchUserPreferenciesForFolderInProject);

        patchEmbeddedFile = jasmine.createSpy("patchEmbeddedFile");
        rewire$patchEmbeddedFile(patchEmbeddedFile);

        addUserLegacyUIPreferency = jasmine.createSpy("addUserLegacyUIPreferency");
        rewire$addUserLegacyUIPreferency(addUserLegacyUIPreferency);

        patchWiki = jasmine.createSpy("patchWiki");
        rewire$patchWiki(patchWiki);

        patchLink = jasmine.createSpy("patchLink");
        rewire$patchLink(patchLink);
    });

    describe("loadRootFolder()", () => {
        it("load document root and then load its own content", async () => {
            const root_item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            const project = {
                additional_informations: {
                    docman: {
                        root_item
                    }
                }
            };

            getProject.and.returnValue(project);

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoading");
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", root_item);
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
            expect(loadFolderContent).toHaveBeenCalled();
            await expectAsync(loadFolderContent.calls.mostRecent().args[2]).toBeResolvedTo(
                root_item
            );
        });

        it("When the user does not have access to the project, an error will be raised", async () => {
            mockFetchError(getProject, {
                status: 403,
                error_json: {
                    error: {
                        message: "User can't access project"
                    }
                }
            });

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith("error/switchFolderPermissionError");
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });

        it("When the project can't be found, an error will be raised", async () => {
            const error_message = "Project does not exist.";
            mockFetchError(getProject, {
                status: 404,
                error_json: {
                    error: {
                        message: error_message
                    }
                }
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
        it("loads ascendant hierarchy and content for stored current folder", async () => {
            const current_folder = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            context.state.current_folder = current_folder;

            await loadFolder(context, 3);

            expect(getItem).not.toHaveBeenCalled();
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expectAsync(loadFolderContent.calls.mostRecent().args[2]).toBeResolvedTo(
                current_folder
            );
            await expectAsync(loadAscendantHierarchy.calls.mostRecent().args[2]).toBeResolvedTo(
                current_folder
            );
        });

        it("gets item if there isn't any current folder in the store", async () => {
            const folder_to_fetch = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            getItem.and.returnValue(Promise.resolve(folder_to_fetch));

            await loadFolder(context, 3);

            expect(getItem).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_to_fetch);
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expectAsync(loadFolderContent.calls.mostRecent().args[2]).toBeResolvedTo(
                folder_to_fetch
            );
            await expectAsync(loadAscendantHierarchy.calls.mostRecent().args[2]).toBeResolvedTo(
                folder_to_fetch
            );
        });

        it("gets item when the requested folder is not in the store", async () => {
            context.state.current_folder = {
                id: 1,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            const folder_to_fetch = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            getItem.and.returnValue(Promise.resolve(folder_to_fetch));

            await loadFolder(context, 3);

            expect(getItem).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_to_fetch);
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expectAsync(loadFolderContent.calls.mostRecent().args[2]).toBeResolvedTo(
                folder_to_fetch
            );
            await expectAsync(loadAscendantHierarchy.calls.mostRecent().args[2]).toBeResolvedTo(
                folder_to_fetch
            );
        });

        it("does not load ascendant hierarchy if folder is already inside the current one", async () => {
            const folder_a = {
                id: 2,
                title: "folder A",
                owner: {
                    id: 101
                },
                last_update_date: "2018-08-07T16:42:49+02:00"
            };
            const folder_b = {
                id: 3,
                title: "folder B",
                owner: {
                    id: 101
                },
                last_update_date: "2018-08-07T16:42:49+02:00"
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
                    id: 101
                },
                last_update_date: "2018-08-07T16:42:49+02:00"
            };
            const folder_b = {
                id: 3,
                title: "folder B",
                owner: {
                    id: 101
                },
                last_update_date: "2018-08-07T16:42:49+02:00"
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
                    id: 101
                },
                last_update_date: "2018-08-07T16:42:49+02:00"
            };
            const folder_b = {
                id: 3,
                title: "folder B",
                owner: {
                    id: 101
                },
                last_update_date: "2018-08-07T16:42:49+02:00"
            };

            context.state.current_folder_ascendant_hierarchy = [folder_a, folder_b];
            context.state.current_folder = folder_a;

            await loadFolder(context, 2);

            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("saveAscendantHierarchy", [folder_a]);
            expect(context.commit).not.toHaveBeenCalledWith("setCurrentFolder", folder_a);
        });
    });

    describe("setUserPreferenciesForFolder", () => {
        it("sets the user preference for the state of a given folder if its new state is 'open' (expanded)", async () => {
            const folder_id = 30;
            const should_be_closed = false;
            const context = {
                state: {
                    user_id: 102,
                    project_id: 110
                }
            };

            await setUserPreferenciesForFolder(context, [folder_id, should_be_closed]);

            expect(patchUserPreferenciesForFolderInProject).toHaveBeenCalled();
            expect(deleteUserPreferenciesForFolderInProject).not.toHaveBeenCalled();
        });

        it("deletes the user preference for the state of a given folder if its new state is 'closed' (collapsed)", async () => {
            const folder_id = 30;
            const should_be_closed = true;
            const context = {
                state: {
                    user_id: 102,
                    project_id: 110
                }
            };

            await setUserPreferenciesForFolder(context, [folder_id, should_be_closed]);

            expect(patchUserPreferenciesForFolderInProject).not.toHaveBeenCalled();
            expect(deleteUserPreferenciesForFolderInProject).toHaveBeenCalled();
        });
    });

    describe("setUserPreferenciesForUI", () => {
        it("sets the user preference to old ui", async () => {
            const context = {
                state: {
                    user_id: 102,
                    project_id: 110
                }
            };

            await setUserPreferenciesForUI(context);

            expect(addUserLegacyUIPreferency).toHaveBeenCalled();
        });
    });

    describe("unsetUnderConstructionUserPreference", () => {
        it("unset the under construction preference", async () => {
            const context = {
                commit: jasmine.createSpy("commit"),
                state: {
                    user_id: 102,
                    project_id: 110
                }
            };

            await unsetUnderConstructionUserPreference(context);

            expect(deleteUserPreferenciesForUnderConstructionModal).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("removeIsUnderConstruction");
        });
    });

    describe("createNewItem", () => {
        it("Creates new document and reload folder content", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.and.returnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            const parent = { id: 2, title: "my folder", type: "folder", is_expanded: true };
            const current_folder = parent;
            getItem.and.returnValue(Promise.resolve(item));

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
                        message: error_message
                    }
                }
            });
            const parent = { id: 2, title: "my folder", type: "folder", is_expanded: true };
            const current_folder = parent;
            const item = { id: 66, title: "", type: "empty" };

            await createNewItem(context, [item, parent, current_folder]);

            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                jasmine.any(Object)
            );
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", error_message);
        });

        it("displays the created item when it is created in the current folder", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.and.returnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            getItem.and.returnValue(Promise.resolve(item));

            const folder_of_created_item = { id: 10 };
            const current_folder = { id: 10 };

            await createNewItem(context, [item, folder_of_created_item, current_folder]);

            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("not displays the created item when it is created in a collapsed folder", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.and.returnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            getItem.and.returnValue(Promise.resolve(item));

            const current_folder = { id: 30 };
            const collapsed_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: false };

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);
            expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
                collapsed_folder_of_created_item,
                item,
                false
            ]);
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("displays the created item when it is created in a expanded folder which is not the same as the current folder", async () => {
            const created_item_reference = { id: 66 };
            addNewEmpty.and.returnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever", type: "empty" };
            getItem.and.returnValue(Promise.resolve(item));

            const current_folder = { id: 18 };
            const collapsed_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: true };

            await createNewItem(context, [item, collapsed_folder_of_created_item, current_folder]);
            expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
        });
        it("displays the created file when it is created in the current folder", async () => {
            context.state.folder_content = [{ id: 10 }];
            const created_item_reference = { id: 66 };

            addNewFile.and.returnValue(Promise.resolve(created_item_reference));
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties }
            };

            getItem.and.returnValue(Promise.resolve(item));
            const folder_of_created_item = { id: 10 };
            const current_folder = { id: 10 };
            const uploader = {};
            uploadFile.and.returnValue(uploader);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null
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
                true
            ]);
            expect(context.commit).toHaveBeenCalledWith(
                "addFileInUploadsList",
                expected_fake_item_with_uploader
            );
        });
        it("not displays the created file when it is created in a collapsed folder and displays the progress bar along the folder", async () => {
            context.state.folder_content = [{ id: 10 }];
            const created_item_reference = { id: 66 };

            addNewFile.and.returnValue(Promise.resolve(created_item_reference));
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties }
            };

            getItem.and.returnValue(Promise.resolve(item));
            const current_folder = { id: 30 };
            const collapsed_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: false };
            const uploader = {};
            uploadFile.and.returnValue(uploader);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null
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
                false
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

            addNewFile.and.returnValue(Promise.resolve(created_item_reference));
            const file_name_properties = { name: "filename.txt", size: 10, type: "text/plain" };
            const item = {
                id: 66,
                title: "filename.txt",
                description: "",
                type: TYPE_FILE,
                file_properties: { file: file_name_properties }
            };

            getItem.and.returnValue(Promise.resolve(item));
            const current_folder = { id: 30 };
            const extended_folder_of_created_item = { id: 10, parent_id: 30, is_expanded: true };
            const uploader = {};
            uploadFile.and.returnValue(uploader);

            const expected_fake_item_with_uploader = {
                id: 66,
                title: "filename.txt",
                parent_id: 10,
                type: TYPE_FILE,
                file_type: "text/plain",
                is_uploading: true,
                progress: 0,
                uploader,
                upload_error: null
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
                true
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
            addNewFile.and.returnValue(Promise.resolve(created_item_reference));
            const uploader = {};
            uploadFile.and.returnValue(uploader);

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
                upload_error: null
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
            addNewFile.and.returnValue(Promise.resolve(created_item_reference));
            const uploader = {};
            uploadFile.and.returnValue(uploader);

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
                upload_error: null
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
            addNewFile.and.returnValue(Promise.resolve(created_item_reference));

            await addNewUploadFile(context, [dropped_file, parent, "filename.txt", "", true]);

            expect(context.commit).not.toHaveBeenCalled();
            expect(uploadFile).not.toHaveBeenCalled();
        });
        it("does not start upload if file is empty", async () => {
            context.state.folder_content = [{ id: 45 }];
            const dropped_file = { name: "empty-file.txt", size: 0, type: "text/plain" };
            const parent = { id: 42 };

            const created_item_reference = { id: 66 };
            addNewFile.and.returnValue(Promise.resolve(created_item_reference));

            const created_item = { id: 66, parent_id: 42, type: "file" };
            getItem.and.returnValue(Promise.resolve(created_item));

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
                    abort: jasmine.createSpy("abort")
                }
            };
        });

        it("asks to tus client to abort the upload", async () => {
            await cancelFileUpload(context, item);
            expect(item.uploader.abort).toHaveBeenCalled();
        });
        it("asks to tus server to abort the upload, because tus client does not do it for us", async () => {
            await cancelFileUpload(context, item);
            expect(cancelUpload).toHaveBeenCalledWith(item);
        });
        it("remove item from the store", async () => {
            await cancelFileUpload(context, item);
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
        });
        it("remove item from the store even if there is an error on cancelUpload", async () => {
            cancelUpload.and.throwError("Failed to fetch");
            await cancelFileUpload(context, item);
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
        });
    });

    describe("cancelVersionUpload", () => {
        let item;
        beforeEach(() => {
            item = {
                uploader: {
                    abort: jasmine.createSpy("abort")
                }
            };
        });

        it("asks to tus client to abort the upload", async () => {
            await cancelVersionUpload(context, item);
            expect(item.uploader.abort).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("removeVersionUploadProgress", item);
        });
    });
    describe("createNewFileVersion", () => {
        it("does not trigger any upload if the file is empty", async () => {
            const dropped_file = { name: "filename.txt", size: 0, type: "text/plain" };
            const item = {};

            createNewVersion.and.returnValue(Promise.resolve());

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).not.toHaveBeenCalled();
        });
        it("upload a new version of file", async () => {
            const item = { id: 45, lock_info: null };
            context.state.folder_content = [{ id: 45 }];
            const dropped_file = { name: "filename.txt", size: 123, type: "text/plain" };

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.and.returnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.and.returnValue(uploader);

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).toHaveBeenCalled();
        });
    });
    describe("createNewFileVersionFromModal", () => {
        it("uploads a new version of a file", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const updated_file = { name: "filename.txt", size: 123, type: "text/plain" };

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.and.returnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.and.returnValue(uploader);

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            await createNewFileVersionFromModal(context, [
                item,
                updated_file,
                version_title,
                version_changelog,
                is_version_locked
            ]);

            expect(createNewVersion).toHaveBeenCalled();
            expect(uploadVersion).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the version creation", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const update_fail = {};

            createNewVersion.and.throwError("An error occurred ");

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";

            await createNewFileVersionFromModal(context, [
                item,
                update_fail,
                version_title,
                version_changelog
            ]);

            expect(createNewVersion).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", jasmine.anything());
            expect(uploadVersion).not.toHaveBeenCalled();
        });
    });

    describe("createNewEmbeddedFileVersionFromModal", () => {
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
                is_version_locked
            ]);

            expect(patchEmbeddedFile).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const new_html_content = { content: "<h1>Hello world!</h1>}}" };

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            patchEmbeddedFile.and.throwError("nope");

            await createNewEmbeddedFileVersionFromModal(context, [
                item,
                new_html_content,
                version_title,
                version_changelog,
                is_version_locked
            ]);

            expect(patchEmbeddedFile).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", jasmine.anything());
        });
    });

    describe("createNewWikiVersionFromModal", () => {
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
                is_version_locked
            ]);

            expect(patchWiki).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const page_name = "kinky wiki";

            const version_title = "NSFW";
            const version_changelog = "Changed title to NSFW";
            const is_version_locked = true;

            patchWiki.and.throwError("nope");

            await createNewWikiVersionFromModal(context, [
                item,
                page_name,
                version_title,
                version_changelog,
                is_version_locked
            ]);

            expect(patchWiki).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", jasmine.anything());
        });
    });

    describe("createNewLinkVersionFromModal", () => {
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
                is_version_locked
            ]);

            expect(patchLink).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 };
            context.state.folder_content = [{ id: 45 }];
            const new_link_url = "https://moogle.fr";

            const version_title = "My new version";
            const version_changelog = "Changed the version because...";
            const is_version_locked = true;

            patchLink.and.throwError("nope");

            await createNewLinkVersionFromModal(context, [
                item,
                new_link_url,
                version_title,
                version_changelog,
                is_version_locked
            ]);

            expect(patchLink).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("error/setModalError", jasmine.anything());
        });
    });

    describe("cancelFolderUpload", () => {
        let folder, item, context;

        beforeEach(() => {
            folder = {
                title: "My folder",
                id: 123
            };

            item = {
                parent_id: folder.id,
                is_uploading_new_version: false,
                uploader: {
                    abort: jasmine.createSpy("abort")
                }
            };

            context = {
                commit: jasmine.createSpy("commit"),
                state: {
                    files_uploads_list: [item]
                }
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
        it("loads ascendant hierarchy and content of item", async () => {
            const current_folder = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            const item = {
                id: 42,
                title: "My embedded file",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };

            context.state.current_folder = current_folder;

            getItem.and.returnValue(Promise.resolve(item));
            loadFolderContent.and.returnValue(Promise.resolve(current_folder));

            await loadDocumentWithAscendentHierarchy(42);
            expect(loadAscendantHierarchy).toHaveBeenCalled();
        });

        it("throw error if something went wrong", async () => {
            getItem.and.throwError("nope");

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith(
                "error/setItemLoadingError",
                "Internal server error"
            );
        });
    });

    describe("deleteItem()", () => {
        let item_to_delete,
            context,
            deleteFile,
            deleteLink,
            deleteEmbeddedFile,
            deleteWiki,
            deleteFolder,
            deleteEmptyDocument;

        beforeEach(() => {
            item_to_delete = {
                id: 123,
                title: "My file",
                type: TYPE_FILE
            };

            context = {
                state: {
                    folder_content: [item_to_delete],
                    currently_previewed_item: null
                },
                commit: jasmine.createSpy("commit")
            };

            deleteFile = jasmine.createSpy("deleteItem");
            rewire$deleteFile(deleteFile);

            deleteLink = jasmine.createSpy("deleteLink");
            rewire$deleteLink(deleteLink);

            deleteEmbeddedFile = jasmine.createSpy("deleteEmbeddedFile");
            rewire$deleteEmbeddedFile(deleteEmbeddedFile);

            deleteWiki = jasmine.createSpy("deleteWiki");
            rewire$deleteWiki(deleteWiki);

            deleteFolder = jasmine.createSpy("deleteFolder");
            rewire$deleteFolder(deleteFolder);

            deleteEmptyDocument = jasmine.createSpy("deleteEmptyDocument");
            rewire$deleteEmptyDocument(deleteEmptyDocument);
        });

        it("when item is a file, then the delete file route is called", async () => {
            const file_item = {
                id: 111,
                title: "My File",
                type: TYPE_FILE
            };

            await deleteItem(context, [file_item]);
            expect(deleteFile).toHaveBeenCalledWith(file_item);
        });

        it("when item is a link, then the delete link route is called", async () => {
            const link_item = {
                id: 222,
                title: "My Link",
                type: TYPE_LINK
            };

            await deleteItem(context, [link_item]);
            expect(deleteLink).toHaveBeenCalledWith(link_item);
        });

        it("when item is an embedded file, then the delete embedded file route is called", async () => {
            const embedded_file_item = {
                id: 222,
                title: "My embedded file",
                type: TYPE_EMBEDDED
            };

            await deleteItem(context, [embedded_file_item]);
            expect(deleteEmbeddedFile).toHaveBeenCalledWith(embedded_file_item);
        });

        it("when item is a wiki, then the delete wiki route is called", async () => {
            const wiki_item = {
                id: 222,
                title: "My Wiki",
                type: TYPE_WIKI
            };

            const additional_options = { delete_associated_wiki_page: true };

            await deleteItem(context, [wiki_item, additional_options]);
            expect(deleteWiki).toHaveBeenCalledWith(wiki_item, additional_options);
        });

        it("when item is an empty document, then the delete empty document route is called", async () => {
            const empty_doc_item = {
                id: 222,
                title: "My empty document",
                type: TYPE_EMPTY
            };

            await deleteItem(context, [empty_doc_item]);
            expect(deleteEmptyDocument).toHaveBeenCalledWith(empty_doc_item);
        });

        it("when item is a folder, then the delete folder route is called", async () => {
            const folder_item = {
                id: 222,
                title: "My folder",
                type: TYPE_FOLDER
            };

            const additional_options = { delete_associated_wiki_page: true };

            await deleteItem(context, [folder_item, additional_options]);
            expect(deleteFolder).toHaveBeenCalledWith(folder_item, additional_options);
        });

        it("deletes the given item and removes it from the tree view", async () => {
            await deleteItem(context, [item_to_delete]);

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                item_to_delete
            );
        });

        it("resets currentlyPreviewedItem when it references the deleted item", async () => {
            context.state.currently_previewed_item = item_to_delete;

            await deleteItem(context, [item_to_delete]);

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                item_to_delete
            );
            expect(context.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", null);
        });
    });

    describe("getWikisReferencingSameWikiPage()", () => {
        let getItemsReferencingSameWikiPage,
            getParents,
            context = {};

        beforeEach(() => {
            getItemsReferencingSameWikiPage = jasmine.createSpy("getItemsReferencingSameWikiPage");
            rewire$getItemsReferencingSameWikiPage(getItemsReferencingSameWikiPage);

            getParents = jasmine.createSpy("getParents");
            rewire$getParents(getParents);
        });

        it("it should return a collection of the items referencing the same wiki page", async () => {
            const wiki_1 = {
                item_name: "wiki 1",
                item_id: 1
            };

            const wiki_2 = {
                item_name: "wiki 2",
                item_id: 2
            };

            getItemsReferencingSameWikiPage.and.returnValue([wiki_1, wiki_2]);

            getParents.withArgs(wiki_1.item_id).and.returnValue(
                Promise.resolve([
                    {
                        title: "Project documentation"
                    }
                ])
            );

            getParents.withArgs(wiki_2.item_id).and.returnValue(
                Promise.resolve([
                    {
                        title: "Project documentation"
                    },
                    {
                        title: "Folder 1"
                    }
                ])
            );

            const target_wiki = {
                title: "wiki 3",
                wiki_properties: {
                    page_name: "A wiki page",
                    page_id: 123
                }
            };

            const referencers = await getWikisReferencingSameWikiPage(context, target_wiki);

            expect(referencers).toEqual([
                {
                    path: "/Project documentation/wiki 1",
                    id: 1
                },
                {
                    path: "/Project documentation/Folder 1/wiki 2",
                    id: 2
                }
            ]);
        });

        it("it should return null if there is a rest exception", async () => {
            getParents.and.returnValue(Promise.reject());

            const target_wiki = {
                title: "wiki 3",
                wiki_properties: {
                    page_name: "A wiki page",
                    page_id: 123
                }
            };

            const referencers = await getWikisReferencingSameWikiPage(context, target_wiki);

            expect(referencers).toEqual(null);
        });
    });

    describe("lockFile()", () => {
        let postLockFile, getItem, context;

        beforeEach(() => {
            context = { commit: jasmine.createSpy("commit") };

            postLockFile = jasmine.createSpy("postLockFile");
            rewire$postLockFile(postLockFile);

            getItem = jasmine.createSpy("getItem");
            rewire$getItem(getItem);
        });

        it("it should lock a file and then update its information", async () => {
            const item_to_lock = {
                id: 123,
                title: "My file",
                type: TYPE_FILE
            };

            getItem.and.returnValue(
                Promise.resolve({
                    id: 123,
                    title: "My file",
                    type: TYPE_FILE,
                    lock_info: {
                        user_id: 123
                    }
                })
            );

            await lockFile(context, item_to_lock);

            expect(context.commit).toHaveBeenCalledWith("replaceLockInfoWithNewVersion", [
                item_to_lock,
                { user_id: 123 }
            ]);
        });
    });

    describe("unlockFile()", () => {
        let deleteLockFile, getItem, context;

        beforeEach(() => {
            context = { commit: jasmine.createSpy("commit") };

            deleteLockFile = jasmine.createSpy("deleteLockFile");
            rewire$deleteLockFile(deleteLockFile);

            getItem = jasmine.createSpy("getItem");
            rewire$getItem(getItem);
        });

        it("it should lock a file and then update its information", async () => {
            const item_to_lock = {
                id: 123,
                title: "My file",
                type: TYPE_FILE
            };

            getItem.and.returnValue(
                Promise.resolve({
                    id: 123,
                    title: "My file",
                    type: TYPE_FILE,
                    lock_info: {
                        user_id: 123
                    }
                })
            );

            await unlockFile(context, item_to_lock);

            expect(context.commit).toHaveBeenCalledWith("replaceLockInfoWithNewVersion", [
                item_to_lock,
                { user_id: 123 }
            ]);
        });
    });
});
