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

import * as load_folder_content from "./actions-helpers/load-folder-content";
import * as rest_querier from "../api/rest-querier";
import * as error_handler from "./actions-helpers/handle-errors";
import {
    getWikisReferencingSameWikiPage,
    loadDocument,
    loadDocumentWithAscendentHierarchy,
    loadFolder,
    loadRootFolder,
} from "./actions-retrieve";
import type { ActionContext } from "vuex";
import type { Folder, Item, RootState, Wiki } from "../type";
import type { ProjectService, RestFolder } from "../api/rest-querier";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import * as load_ascendant_hierarchy from "./actions-helpers/load-ascendant-hierarchy";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { TYPE_FOLDER } from "../constants";

describe("actions-get", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        const project_id = 101;
        context = {
            commit: jest.fn(),
            state: {
                configuration: { project_id },
                current_folder_ascendant_hierarchy: [],
            },
        } as unknown as ActionContext<RootState, RootState>;
        jest.resetAllMocks();
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
                type: TYPE_FOLDER,
                metadata: [],
            } as unknown as RestFolder;

            const item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
                type: TYPE_FOLDER,
                properties: [],
            } as unknown as Item;

            const service = {
                root_item,
            } as ProjectService;

            const loadFolderContent = jest.spyOn(load_folder_content, "loadFolderContent");
            jest.spyOn(rest_querier, "getDocumentManagerServiceInformation").mockResolvedValue(
                service,
            );
            jest.spyOn(rest_querier, "getFolderContent").mockResolvedValue([item]);
            const handle_error = jest.spyOn(error_handler, "handleErrors");

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoading");
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", {
                ...root_item,
                properties: [],
            });
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
            expect(handle_error).not.toHaveBeenCalled();
            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2],
            ).resolves.toStrictEqual({
                ...root_item,
                properties: [],
            });
        });

        it("When the user does not have access to the project, an error will be raised", async () => {
            jest.spyOn(rest_querier, "getDocumentManagerServiceInformation").mockReturnValue(
                Promise.reject(
                    new FetchWrapperError("", {
                        ok: false,
                        status: 403,
                        statusText: "",
                        json: () =>
                            Promise.resolve({
                                error: {
                                    message: "User can't access project",
                                },
                            }),
                    } as Response),
                ),
            );

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith("error/switchFolderPermissionError");
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });

        it("When the project can't be found, an error will be raised", async () => {
            const error_message = "Project does not exist.";
            jest.spyOn(rest_querier, "getDocumentManagerServiceInformation").mockReturnValue(
                Promise.reject(
                    new FetchWrapperError("", {
                        ok: false,
                        status: 404,
                        statusText: "",
                        json: () =>
                            Promise.resolve({
                                error: {
                                    message: error_message,
                                },
                            }),
                    } as Response),
                ),
            );

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith(
                "error/setFolderLoadingError",
                error_message,
            );
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });

        it("When an error occurred, then the translated exception will be raised", async () => {
            const error_message = "My translated exception";
            jest.spyOn(rest_querier, "getDocumentManagerServiceInformation").mockReturnValue(
                Promise.reject(
                    new FetchWrapperError("", {
                        ok: false,
                        status: 404,
                        statusText: "",
                        json: () =>
                            Promise.resolve({
                                error: {
                                    i18n_error_message: error_message,
                                },
                            }),
                    } as Response),
                ),
            );

            await loadRootFolder(context);

            expect(context.commit).toHaveBeenCalledWith(
                "error/setFolderLoadingError",
                error_message,
            );
            expect(context.commit).toHaveBeenCalledWith("stopLoading");
        });
    });

    describe("loadFolder", () => {
        let getItem: jest.SpyInstance,
            loadFolderContent: jest.SpyInstance,
            loadAscendantHierarchy: jest.SpyInstance;

        beforeEach(() => {
            getItem = jest.spyOn(rest_querier, "getItem");
            loadFolderContent = jest
                .spyOn(load_folder_content, "loadFolderContent")
                .mockReturnValue(Promise.resolve());
            loadAscendantHierarchy = jest
                .spyOn(load_ascendant_hierarchy, "loadAscendantHierarchy")
                .mockReturnValue(Promise.resolve());
        });

        it("loads ascendant hierarchy and content for stored current folder", async () => {
            const current_folder = {
                id: 3,
                type: TYPE_FOLDER,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            } as Folder;

            context.state.current_folder = current_folder;

            await loadFolder(context, 3);

            expect(getItem).not.toHaveBeenCalled();
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();

            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2],
            ).resolves.toStrictEqual(current_folder);
            await expect(
                loadAscendantHierarchy.mock.calls[loadAscendantHierarchy.mock.calls.length - 1][2],
            ).resolves.toStrictEqual(current_folder);
        });

        it("gets item if there isn't any current folder in the store", async () => {
            const folder_to_fetch = {
                id: 3,
                type: TYPE_FOLDER,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            } as Folder;

            getItem.mockReturnValue(Promise.resolve(folder_to_fetch));

            await loadFolder(context, 3);

            expect(getItem).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_to_fetch);
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2],
            ).resolves.toStrictEqual(folder_to_fetch);
            await expect(
                loadAscendantHierarchy.mock.calls[loadAscendantHierarchy.mock.calls.length - 1][2],
            ).resolves.toStrictEqual(folder_to_fetch);
        });

        it("gets item when the requested folder is not in the store", async () => {
            context.state.current_folder = {
                id: 1,
                type: TYPE_FOLDER,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            } as Folder;

            const folder_to_fetch = {
                id: 3,
                type: TYPE_FOLDER,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            } as Folder;

            getItem.mockReturnValue(Promise.resolve(folder_to_fetch));

            await loadFolder(context, 3);

            expect(getItem).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("setCurrentFolder", folder_to_fetch);
            expect(loadFolderContent).toHaveBeenCalled();
            expect(loadAscendantHierarchy).toHaveBeenCalled();
            await expect(
                loadFolderContent.mock.calls[loadFolderContent.mock.calls.length - 1][2],
            ).resolves.toStrictEqual(folder_to_fetch);
            await expect(
                loadAscendantHierarchy.mock.calls[loadAscendantHierarchy.mock.calls.length - 1][2],
            ).resolves.toStrictEqual(folder_to_fetch);
        });

        it("does not load ascendant hierarchy if folder is already inside the current one", async () => {
            const folder_a = {
                id: 2,
                type: TYPE_FOLDER,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder;
            const folder_b = {
                id: 3,
                type: TYPE_FOLDER,
                title: "folder B",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder;

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
                type: TYPE_FOLDER,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder;
            const folder_b = {
                id: 3,
                type: TYPE_FOLDER,
                title: "folder B",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder;

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
                type: TYPE_FOLDER,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder;
            const folder_b = {
                id: 3,
                type: TYPE_FOLDER,
                title: "folder B",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder;

            context.state.current_folder_ascendant_hierarchy = [folder_a, folder_b];
            context.state.current_folder = folder_a;

            await loadFolder(context, 2);

            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("saveAscendantHierarchy", [folder_a]);
            expect(context.commit).not.toHaveBeenCalledWith("setCurrentFolder", folder_a);
        });
    });

    describe("loadDocumentWithAscendentHierarchy", () => {
        let loadAscendantHierarchy: jest.SpyInstance;

        beforeEach(() => {
            loadAscendantHierarchy = jest.spyOn(load_ascendant_hierarchy, "loadAscendantHierarchy");
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
            } as Folder;

            const item = {
                id: 42,
                parent_id: 3,
                title: "My embedded file",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            } as Item;

            context.state.current_folder = current_folder;

            const retrieved_folder_promise = Promise.resolve(current_folder);

            jest.spyOn(rest_querier, "getItem").mockImplementation((item_id: number) => {
                if (item_id === 42) {
                    return Promise.resolve(item);
                } else if (item_id === 3) {
                    return retrieved_folder_promise;
                }
                throw Error("Unknown item");
            });
            loadAscendantHierarchy.mockReturnValue(Promise.resolve());

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).toHaveBeenCalledWith(
                context,
                3,
                retrieved_folder_promise,
            );
        });

        it("throw error if something went wrong", async () => {
            jest.spyOn(rest_querier, "getItem").mockImplementation(() => {
                throw new Error("nope");
            });

            await expect(loadDocumentWithAscendentHierarchy(context, 42)).rejects.toBeDefined();
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "error/setItemLoadingError",
                "Internal server error",
            );
        });

        it("throw error permission error if user does not have enough permissions", async () => {
            const getItem: jest.SpyInstance = jest.spyOn(rest_querier, "getItem");
            mockFetchError(getItem, {
                status: 403,
            });

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith("error/switchItemPermissionError");
        });

        it("throw translated exceptions", async () => {
            const getItem: jest.SpyInstance = jest.spyOn(rest_querier, "getItem");
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
                "My translated error",
            );
        });

        it("throw internal server error if something bad happens", async () => {
            const getItem: jest.SpyInstance = jest.spyOn(rest_querier, "getItem");
            mockFetchError(getItem, {
                status: 400,
            });

            await loadDocumentWithAscendentHierarchy(context, 42);
            expect(loadAscendantHierarchy).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith(
                "error/setItemLoadingError",
                "Internal server error",
            );
        });
    });

    describe("loadDocument", () => {
        it("loads an item", async () => {
            const item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            } as Item;

            const getItem = jest
                .spyOn(rest_querier, "getItem")
                .mockReturnValue(Promise.resolve(item));

            await loadDocument(context, 3);

            expect(getItem).toHaveBeenCalled();
        });

        it("handle error when document load fails", async () => {
            const getItem = jest
                .spyOn(rest_querier, "getItem")
                .mockReturnValue(Promise.reject("error"));

            await expect(loadDocument(context, 3)).rejects.toBeDefined();
            expect(getItem).toHaveBeenCalled();
        });
    });

    describe("getWikisReferencingSameWikiPage()", () => {
        let getItemsReferencingSameWikiPage: jest.SpyInstance, getParents: jest.SpyInstance;

        beforeEach(() => {
            getItemsReferencingSameWikiPage = jest.spyOn(
                rest_querier,
                "getItemsReferencingSameWikiPage",
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
                    ]),
                )
                .mockReturnValueOnce(
                    Promise.resolve([
                        {
                            title: "Project documentation",
                        },
                        {
                            title: "Folder 1",
                        },
                    ]),
                );

            const target_wiki = {
                title: "wiki 3",
                wiki_properties: {
                    page_name: "A wiki page",
                    page_id: 123,
                },
            } as Wiki;

            const referencers = await getWikisReferencingSameWikiPage(context, target_wiki);

            expect(referencers).toStrictEqual([
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
            } as Wiki;

            const referencers = await getWikisReferencingSameWikiPage(context, target_wiki);

            expect(referencers).toBeNull();
        });
    });
});
