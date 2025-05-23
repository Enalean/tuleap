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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    getFolderProperties,
    loadProjectProperties,
    updateFolderProperties,
    updateProperties,
} from "./properties-actions";
import * as properties_rest_querier from "../../api/properties-rest-querier";
import * as rest_querier from "../../api/rest-querier";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import type { ActionContext } from "vuex";
import type {
    Embedded,
    Empty,
    Folder,
    ItemFile,
    Link,
    RootState,
    Wiki,
    Property,
    ListValue,
} from "../../type";
import type { PropertiesState } from "./module";
import emitter from "../../helpers/emitter";

vi.mock("../../helpers/emitter");

describe("Properties actions", () => {
    let context: ActionContext<PropertiesState, RootState>, getProjectProperties: vi.SpyInstance;

    beforeEach(() => {
        context = {
            rootState: {
                configuration: { project_id: 102 },
            },
            commit: vi.fn(),
            dispatch: vi.fn(),
        } as unknown as ActionContext<PropertiesState, RootState>;

        getProjectProperties = vi.spyOn(properties_rest_querier, "getProjectProperties");

        vi.clearAllMocks();
    });

    it(`load project properties definition`, async () => {
        const properties = [
            {
                short_name: "text",
                type: "text",
            },
        ];

        getProjectProperties.mockReturnValue(properties);

        await loadProjectProperties(context);

        expect(context.commit).toHaveBeenCalledWith("saveProjectProperties", properties);
    });

    it("Handle error when properties project load fails", async () => {
        mockFetchError(getProjectProperties, {
            status: 400,
            error_json: {
                error: {
                    message: "Something bad happens",
                },
            },
        });

        await loadProjectProperties(context);

        expect(context.dispatch).toHaveBeenCalled();
    });

    describe("replacePropertiesWithUpdatesOnes", () => {
        let context: ActionContext<PropertiesState, RootState>, getItem: vi.SpyInstance;

        beforeEach(() => {
            context = {
                rootState: {
                    configuration: { is_status_property_used: false },
                },
                commit: vi.fn(),
                dispatch: vi.fn(),
            } as unknown as ActionContext<PropertiesState, RootState>;

            getItem = vi.spyOn(rest_querier, "getItem");
        });

        describe("Given item is not the current folder -", () => {
            it("should send null when obsolescence date is permanent", async () => {
                vi.spyOn(properties_rest_querier, "putFileProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
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
                } as ItemFile;

                const properties: Array<Property> = [];
                const item_to_update = {
                    id: 123,
                    title: "My new title",
                    type: TYPE_FILE,
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    properties,
                } as ItemFile;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });

            it("should update file properties", async () => {
                vi.spyOn(properties_rest_querier, "putFileProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
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
                } as ItemFile;

                const item_to_update = {
                    id: 123,
                    title: "My new title",
                    type: TYPE_FILE,
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                } as ItemFile;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });
            it("should update embedded file properties", async () => {
                vi.spyOn(properties_rest_querier, "putEmbeddedFileProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
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
                } as Embedded;

                const properties: Array<Property> = [];
                const item_to_update = {
                    id: 123,
                    title: "My new embedded  title",
                    type: TYPE_EMBEDDED,
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    properties,
                } as Embedded;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });
            it("should update link document properties", async () => {
                vi.spyOn(properties_rest_querier, "putLinkProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
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
                } as Link;

                const properties: Array<Property> = [];
                const item_to_update = {
                    id: 123,
                    title: "My new link title",
                    type: TYPE_LINK,
                    description: "My link description",
                    owner: {
                        id: 102,
                    },
                    status: "draft",
                    obsolescence_date: null,
                    properties,
                } as Link;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                const current_folder = {
                    id: 456,
                } as Folder;

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });

            it("should update wiki document properties", async () => {
                vi.spyOn(properties_rest_querier, "putWikiProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
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
                } as Wiki;

                const properties: Array<Property> = [];
                const item_to_update = {
                    id: 123,
                    title: "My new wiki title",
                    type: TYPE_WIKI,
                    description: "My wiki description",
                    owner: {
                        id: 102,
                    },
                    status: "approved",
                    obsolescence_date: null,
                    properties,
                } as Wiki;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });
            it("should update empty document properties", async () => {
                vi.spyOn(properties_rest_querier, "putEmptyDocumentProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
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
                } as Empty;

                const properties: Array<Property> = [];
                const item_to_update = {
                    id: 123,
                    title: "My new empty title",
                    type: TYPE_EMPTY,
                    description: "My empty description",
                    owner: {
                        id: 102,
                    },
                    status: "rejected",
                    obsolescence_date: null,
                    properties,
                } as Empty;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });

            it("should update folder properties", async () => {
                vi.spyOn(properties_rest_querier, "putFolderDocumentProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
                );
                const item = {
                    id: 123,
                    title: "My folder",
                    type: TYPE_FOLDER,
                    description: "on",
                    owner: {
                        id: 102,
                    },
                } as Folder;

                const list_values: Array<ListValue> = [
                    {
                        id: 103,
                    } as ListValue,
                ];
                const folder_properties: Property = {
                    short_name: "status",
                    list_value: list_values,
                } as Property;
                const properties: Array<Property> = [folder_properties];
                const item_to_update = {
                    id: 123,
                    title: "My new empty title",
                    type: TYPE_FOLDER,
                    description: "My empty description",
                    owner: {
                        id: 102,
                    },
                    properties,
                    status: {
                        value: "rejected",
                        recursion: "all_item",
                    },
                } as Folder;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                const properties_to_update: Array<string> = [];
                await updateFolderProperties(context, {
                    item,
                    item_to_update,
                    current_folder,
                    properties_to_update,
                    recursion_option: "all_item",
                });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "removeItemFromFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "addJustCreatedItemToFolderContent",
                    item_to_update,
                    { root: true },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "updateCurrentItemForQuickLokDisplay",
                    item_to_update,
                    { root: true },
                );
            });

            it("should update folder properties with status recursion", async () => {
                context = {
                    rootState: {
                        configuration: {
                            is_status_property_used: true,
                        },
                    },
                    commit: vi.fn(),
                    dispatch: vi.fn(),
                } as unknown as ActionContext<PropertiesState, RootState>;

                const put_rest_mock = vi
                    .spyOn(properties_rest_querier, "putFolderDocumentProperties")
                    .mockReturnValue(Promise.resolve({} as unknown as Response));
                const item = {
                    id: 123,
                    title: "My folder",
                    type: TYPE_FOLDER,
                    description: "on",
                    owner: {
                        id: 102,
                    },
                } as Folder;

                const list_values: Array<ListValue> = [
                    {
                        id: 103,
                    } as ListValue,
                ];
                const folder_properties: Property = {
                    short_name: "status",
                    list_value: list_values,
                } as Property;
                const properties: Array<Property> = [folder_properties];
                const item_to_update = {
                    id: 123,
                    title: "My new empty title",
                    description: "My empty description",
                    type: TYPE_FOLDER,
                    owner: {
                        id: 102,
                    },
                    properties,
                    status: {
                        value: "rejected",
                        recursion: "all_item",
                    },
                } as Folder;

                const current_folder = {
                    id: 456,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, {
                    item,
                    item_to_update,
                    current_folder,
                });

                expect(put_rest_mock).toHaveBeenCalledWith(
                    123,
                    "My new empty title",
                    "My empty description",
                    102,
                    {
                        value: "rejected",
                        recursion: "all_item",
                    },
                    null,
                    expect.anything(),
                );
            });
        });

        it("should update folder properties without status recursion", async () => {
            context = {
                rootState: {
                    configuration: {
                        is_status_property_used: false,
                    },
                },
                commit: vi.fn(),
                dispatch: vi.fn(),
            } as unknown as ActionContext<PropertiesState, RootState>;

            const put_rest_mock = vi
                .spyOn(properties_rest_querier, "putFolderDocumentProperties")
                .mockReturnValue(Promise.resolve({} as unknown as Response));
            const item = {
                id: 123,
                title: "My folder",
                type: TYPE_FOLDER,
                description: "on",
                owner: {
                    id: 102,
                },
            } as Folder;

            const list_values: Array<ListValue> = [
                {
                    id: 103,
                } as ListValue,
            ];
            const folder_properties: Property = {
                short_name: "status",
                list_value: list_values,
            } as Property;
            const properties: Array<Property> = [folder_properties];
            const item_to_update = {
                id: 123,
                title: "My new empty title",
                description: "My empty description",
                type: TYPE_FOLDER,
                owner: {
                    id: 102,
                },
                properties,
                status: {
                    value: "rejected",
                    recursion: "all_item",
                },
            } as Folder;

            const current_folder = {
                id: 456,
            } as Folder;

            getItem.mockReturnValue(Promise.resolve(item_to_update));

            await updateProperties(context, {
                item,
                item_to_update,
                current_folder,
            });

            expect(put_rest_mock).toHaveBeenCalledWith(
                123,
                "My new empty title",
                "My empty description",
                102,
                {
                    value: "rejected",
                    recursion: "none",
                },
                null,
                expect.anything(),
            );
        });

        describe("Given I'm updating current folder -", () => {
            it("should update file properties", async () => {
                vi.spyOn(properties_rest_querier, "putFolderDocumentProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
                );

                const item = {
                    id: 123,
                    title: "My folder",
                    type: TYPE_FOLDER,
                    description: "n",
                    owner: {
                        id: 102,
                    },
                    status: {
                        value: "none",
                        recursion: "none",
                    },
                    obsolescence_date: null,
                } as Folder;

                const properties: Array<Property> = [];
                const item_to_update = {
                    id: 123,
                    title: "My new title",
                    type: TYPE_FOLDER,
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    status: {
                        value: "draft",
                        recursion: "none",
                    },
                    obsolescence_date: null,
                    properties,
                } as Folder;

                const current_folder = {
                    id: 123,
                } as Folder;

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await updateProperties(context, { item, item_to_update, current_folder });

                expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
                expect(context.commit).toHaveBeenCalledWith(
                    "replaceCurrentFolder",
                    item_to_update,
                    { root: true },
                );
                expect(context.dispatch).toHaveBeenCalledWith("loadFolder", current_folder.id, {
                    root: true,
                });
            });
        });
    });

    describe("getFolderProperties", () => {
        it("Given a folder item, it's properties are fetched and returned", async () => {
            const getItemWithSize = vi.spyOn(rest_querier, "getItemWithSize").mockReturnValue(
                Promise.resolve({
                    id: 3,
                    title: "Project Documentation",
                    folder_properties: {
                        total_size: 102546950,
                        nb_files: 27,
                    },
                } as Folder),
            );

            const properties = await getFolderProperties(context, {
                id: 3,
                title: "Project Documentation",
            } as Folder);

            expect(getItemWithSize).toHaveBeenCalled();
            expect(properties).toStrictEqual({
                total_size: 102546950,
                nb_files: 27,
            });
        });

        it("Handles errors when it fails", async () => {
            const getItemWithSize = vi
                .spyOn(rest_querier, "getItemWithSize")
                .mockReturnValue(Promise.reject("error"));

            const folder = await getFolderProperties(context, {
                id: 3,
                title: "Project Documentation",
            } as Folder);

            expect(getItemWithSize).toHaveBeenCalled();
            expect(folder).toBeNull();
            expect(context.dispatch).toHaveBeenCalled();
        });
    });
});
