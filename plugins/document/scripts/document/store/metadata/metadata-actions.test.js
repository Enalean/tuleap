/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import {
    getFolderProperties,
    loadProjectMetadata,
    updateFolderMetadata,
    updateMetadata,
} from "./metadata-actions.js";
import * as handle_errors from "../actions-helpers/handle-errors";
import * as metadata_rest_querier from "../../api/metadata-rest-querier";
import * as rest_querier from "../../api/rest-querier";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";

describe("Metadata actions", () => {
    let context, getProjectMetadata, handleErrors, global_context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        };

        global_context = {
            state: {
                configuration: { project_id: 102 },
            },
        };
        getProjectMetadata = jest.spyOn(metadata_rest_querier, "getProjectMetadata");
        handleErrors = jest.spyOn(handle_errors, "handleErrors").mockReturnValue(Promise.resolve());
    });

    it(`load project metadata definition`, async () => {
        const metadata = [
            {
                short_name: "text",
                type: "text",
            },
        ];

        getProjectMetadata.mockReturnValue(metadata);

        await loadProjectMetadata(context, [global_context]);

        expect(context.commit).toHaveBeenCalledWith("saveProjectMetadata", metadata);
    });

    it("Handle error when metadata project load fails", async () => {
        mockFetchError(getProjectMetadata, {
            status: 400,
            error_json: {
                error: {
                    message: "Something bad happens",
                },
            },
        });

        await loadProjectMetadata(context, [{ state: { configuration: { project_id: 102 } } }]);

        expect(handleErrors).toHaveBeenCalled();
    });

    describe("replaceMetadataWithUpdatesOnes", () => {
        let getItem, context;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
                dispatch: jest.fn(),
            };

            getItem = jest.spyOn(rest_querier, "getItem");
        });

        describe("Given item is not the current folder -", () => {
            it("should send null when obsolesence date is permanent", async () => {
                jest.spyOn(metadata_rest_querier, "putFileMetadata").mockReturnValue(
                    Promise.resolve()
                );

                const item = {
                    id: 123,
                    title: "My file",
                    type: TYPE_FILE,
                    description: "n",
                    owner: {
                        id: 102,
                    },
                    status: "none",
                    obsolescence_date: "",
                };

                const item_to_update = {
                    id: 123,
                    title: "My new title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    metadata: [],
                };

                const current_folder = {
                    id: 456,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateMetadata(context, [item, item_to_update, current_folder], {
                    root: true,
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });
            it("should update file metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putFileMetadata").mockReturnValue(
                    Promise.resolve()
                );

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

                const item_to_update = {
                    id: 123,
                    title: "My new title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    metadata: [],
                };

                const current_folder = {
                    id: 456,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateMetadata(context, [item, item_to_update, current_folder], {
                    root: true,
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });
            it("should update embedded file metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putEmbeddedFileMetadata").mockReturnValue(
                    Promise.resolve()
                );
                const item = {
                    id: 123,
                    title: "My embedded file",
                    type: TYPE_EMBEDDED,
                    description: "nop",
                    owner: {
                        id: 102,
                    },
                    status: "none",
                    obsolescence_date: null,
                };

                const item_to_update = {
                    id: 123,
                    title: "My new embedded  title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    metadata: [],
                };

                const current_folder = {
                    id: 456,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateMetadata(context, [item, item_to_update, current_folder], {
                    root: true,
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });
            it("should update link document metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putLinkMetadata").mockReturnValue(
                    Promise.resolve()
                );
                const item = {
                    id: 123,
                    title: "My link",
                    type: TYPE_LINK,
                    description: "ui",
                    owner: {
                        id: 102,
                    },
                    status: "none",
                    obsolescence_date: null,
                };

                const item_to_update = {
                    id: 123,
                    title: "My new link title",
                    description: "My link description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    metadata: [],
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                const current_folder = {
                    id: 456,
                };

                await updateMetadata(context, [item, item_to_update, current_folder], {
                    root: true,
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });

            it("should update wiki document metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putWikiMetadata").mockReturnValue(
                    Promise.resolve()
                );
                const item = {
                    id: 123,
                    title: "My wiki",
                    type: TYPE_WIKI,
                    description: "on",
                    owner: {
                        id: 102,
                    },
                    status: "none",
                    obsolescence_date: null,
                };

                const item_to_update = {
                    id: 123,
                    title: "My new wiki title",
                    description: "My wiki description",
                    owner: {
                        id: 102,
                    },
                    status: "approved",
                    obsolescence_date: null,
                    metadata: [],
                };

                const current_folder = {
                    id: 456,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateMetadata(context, [item, item_to_update, current_folder], {
                    root: true,
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });
            it("should update empty document metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putEmptyDocumentMetadata").mockReturnValue(
                    Promise.resolve()
                );
                const item = {
                    id: 123,
                    title: "My empty",
                    type: TYPE_EMPTY,
                    description: "on",
                    owner: {
                        id: 102,
                    },
                    status: "none",
                    obsolescence_date: null,
                };

                const item_to_update = {
                    id: 123,
                    title: "My new empty title",
                    description: "My empty description",
                    owner: {
                        id: 102,
                    },
                    status: "rejected",
                    obsolescence_date: null,
                    metadata: [],
                };

                const current_folder = {
                    id: 456,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateMetadata(context, [item, item_to_update, current_folder], {
                    root: true,
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });

            it("should update folder metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putEmptyDocumentMetadata").mockReturnValue(
                    Promise.resolve()
                );
                const item = {
                    id: 123,
                    title: "My folder",
                    type: TYPE_FOLDER,
                    description: "on",
                    owner: {
                        id: 102,
                    },
                };

                const item_to_update = {
                    id: 123,
                    title: "My new empty title",
                    description: "My empty description",
                    owner: {
                        id: 102,
                    },
                    metadata: [
                        {
                            short_name: "status",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                    ],
                    status: {
                        value: "rejected",
                        recursion: "none",
                    },
                };

                const current_folder = {
                    id: 456,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateFolderMetadata(context, [
                    item,
                    item_to_update,
                    current_folder,
                    [],
                    "none",
                ]);

                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true }
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true }
                );
            });
        });

        describe("Given I'm updating current folder -", () => {
            it("should update file metadata", async () => {
                jest.spyOn(metadata_rest_querier, "putFileMetadata").mockReturnValue(
                    Promise.resolve()
                );

                const item = {
                    id: 123,
                    title: "My folder",
                    type: TYPE_FOLDER,
                    description: "n",
                    owner: {
                        id: 102,
                    },
                    status: "none",
                    obsolescence_date: null,
                };

                const item_to_update = {
                    id: 123,
                    title: "My new title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    metadata: [],
                };

                const current_folder = {
                    id: 123,
                };

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateMetadata(context, [item, item_to_update, current_folder]);

                expect(context.commit).toHaveBeenCalledWith(
                    "replaceCurrentFolder",
                    item_to_update,
                    { root: true }
                );
                expect(context.dispatch).toHaveBeenCalledWith("loadFolder", current_folder.id, {
                    root: true,
                });
            });
        });
    });

    describe("getFolderProperties", () => {
        it("Given a folder item, it's properties are fetched and returned", async () => {
            const getItemWithSize = jest.spyOn(rest_querier, "getItemWithSize").mockReturnValue(
                Promise.resolve({
                    id: 3,
                    title: "Project Documentation",
                    folder_properties: {
                        total_size: 102546950,
                        nb_files: 27,
                    },
                })
            );

            const properties = await getFolderProperties(context, [
                {
                    id: 3,
                    title: "Project Documentation",
                },
            ]);

            expect(getItemWithSize).toHaveBeenCalled();
            expect(properties).toEqual({
                total_size: 102546950,
                nb_files: 27,
            });
        });

        it("Handles errors when it fails", async () => {
            const getItemWithSize = jest
                .spyOn(rest_querier, "getItemWithSize")
                .mockReturnValue(Promise.reject("error"));

            await expect(
                getFolderProperties(context, [
                    {
                        id: 3,
                        title: "Project Documentation",
                    },
                ])
            ).rejects.toBeDefined();

            expect(getItemWithSize).toHaveBeenCalled();
        });
    });
});
