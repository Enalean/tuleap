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
    loadRootFolder,
    loadFolder,
    setUserPreferenciesForFolder,
    createNewDocument
} from "./actions.js";
import {
    restore as restoreRestQuerier,
    rewire$getItem,
    rewire$getProject,
    rewire$deleteUserPreferenciesForFolderInProject,
    rewire$patchUserPreferenciesForFolderInProject,
    rewire$addNewDocument
} from "../api/rest-querier.js";
import {
    restore as restoreLoadFolderContent,
    rewire$loadFolderContent
} from "./actions-helpers/load-folder-content.js";
import {
    restore as restoreLoadAscendantHierarchy,
    rewire$loadAscendantHierarchy
} from "./actions-helpers/load-ascendant-hierarchy.js";

describe("Store actions", () => {
    afterEach(() => {
        restoreRestQuerier();
        restoreLoadFolderContent();
        restoreLoadAscendantHierarchy();
    });

    let context,
        getProject,
        getItem,
        loadFolderContent,
        loadAscendantHierarchy,
        deleteUserPreferenciesForFolderInProject,
        patchUserPreferenciesForFolderInProject,
        addNewDocument;

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

        addNewDocument = jasmine.createSpy("addNewDocument");
        rewire$addNewDocument(addNewDocument);

        deleteUserPreferenciesForFolderInProject = jasmine.createSpy(
            "deleteUserPreferenciesForFolderInProject"
        );
        rewire$deleteUserPreferenciesForFolderInProject(deleteUserPreferenciesForFolderInProject);

        patchUserPreferenciesForFolderInProject = jasmine.createSpy(
            "patchUserPreferenciesForFolderInProject"
        );
        rewire$patchUserPreferenciesForFolderInProject(patchUserPreferenciesForFolderInProject);
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

            expect(context.commit).toHaveBeenCalledWith("switchFolderPermissionError");
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

            expect(context.commit).toHaveBeenCalledWith("setFolderLoadingError", error_message);
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

    describe("createNewDocument", () => {
        it("Creates new document and reload folder content", async () => {
            const created_item_reference = { id: 66 };
            addNewDocument.and.returnValue(Promise.resolve(created_item_reference));

            const item = { id: 66, title: "whatever" };
            getItem.and.returnValue(Promise.resolve(item));

            await createNewDocument(context, ["title", "", "empty", 2]);

            expect(getItem).toHaveBeenCalledWith(66);
            expect(context.commit).toHaveBeenCalledWith("addJustCreatedItemToFolderContent", item);
            expect(context.commit).not.toHaveBeenCalledWith("setModalError");
        });

        it("Stores error when document creation fail", async () => {
            const error_message = "`title` is required.";
            mockFetchError(addNewDocument, {
                status: 400,
                error_json: {
                    error: {
                        message: error_message
                    }
                }
            });
            await createNewDocument(context, ["", "", "empty", 2]);

            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                jasmine.any(Object)
            );
            expect(context.commit).toHaveBeenCalledWith("setModalError", error_message);
        });
    });
});
