/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { getDocumentProperties } from "./document-properties";
import type { Folder, ListValue, Property, RootState } from "../../type";
import type { ActionContext } from "vuex";
import * as rest_querier from "../../api/rest-querier";
import { FolderBuilder } from "../../../tests/builders/FolderBuilder";
import * as properties_rest_querier from "../../api/properties-rest-querier";
import emitter from "../../helpers/emitter";
import { UserBuilder } from "../../../tests/builders/UserBuilder";
import { PropertyBuilder } from "../../../tests/builders/PropertyBuilder";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

vi.mock("../../helpers/emitter");

describe("document-properties", () => {
    const document_properties = getDocumentProperties();
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        context = {
            commit: vi.fn(),
            dispatch: vi.fn(),
        } as unknown as ActionContext<RootState, RootState>;
    });

    describe("getFolderProperties", () => {
        it("Given a folder item, it's properties are fetched and returned", async () => {
            const getItemWithSize = vi.spyOn(rest_querier, "getItemWithSize").mockReturnValue(
                Promise.resolve(
                    new FolderBuilder(3)
                        .withTitle("Project Documentation")
                        .withFolderProperties({
                            total_size: 102546950,
                            nb_files: 27,
                        })
                        .build(),
                ),
            );

            const properties = await document_properties.getFolderProperties(
                context,
                new FolderBuilder(3).withTitle("Project Documentation").build(),
            );

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

            const folder = await document_properties.getFolderProperties(
                context,
                new FolderBuilder(3).withTitle("Project Documentation").build(),
            );

            expect(getItemWithSize).toHaveBeenCalled();
            expect(folder).toBeNull();
            expect(context.dispatch).toHaveBeenCalled();
        });
    });

    describe("updateFolderProperties", () => {
        let getItem: MockInstance;

        beforeEach(() => {
            getItem = vi.spyOn(rest_querier, "getItem");
        });

        it("should update folder properties", async () => {
            vi.spyOn(properties_rest_querier, "putFolderDocumentProperties").mockReturnValue(
                Promise.resolve({} as unknown as Response),
            );
            const item = new FolderBuilder(123)
                .withTitle("My folder")
                .withDescription("on")
                .withOwner(new UserBuilder(102).build())
                .build();

            const list_values: Array<ListValue> = [{ id: 103, name: "" }];
            const folder_properties = new PropertyBuilder()
                .withShortName("status")
                .withListValue(list_values)
                .build();
            const properties: Array<Property> = [folder_properties];
            const item_to_update = new FolderBuilder(123)
                .withTitle("My new empty title")
                .withDescription("My empty description")
                .withOwner(new UserBuilder(102).build())
                .withProperties(properties)
                .withStatus({
                    value: "rejected",
                    recursion: "all_item",
                })
                .build();

            const current_folder = new FolderBuilder(456).build();

            getItem.mockReturnValue(Promise.resolve(item_to_update));

            const properties_to_update: Array<string> = [];
            await document_properties.updateFolderProperties(
                context,
                item,
                item_to_update,
                current_folder,
                properties_to_update,
                "all_item",
                false,
            );

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
    });

    describe("updateProperties", () => {
        let getItem: MockInstance;

        beforeEach(() => {
            getItem = vi.spyOn(rest_querier, "getItem");
        });

        describe("Given item is not the current folder -", () => {
            it("should send null when obsolescence date is permanent", async () => {
                vi.spyOn(properties_rest_querier, "putFileProperties").mockReturnValue(
                    Promise.resolve({} as unknown as Response),
                );

                const item = new ItemBuilder(123)
                    .withTitle("My file")
                    .withType(TYPE_FILE)
                    .withDescription("n")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("none")
                    .build();

                const properties: Array<Property> = [];
                const item_to_update = new ItemBuilder(123)
                    .withTitle("My new title")
                    .withType(TYPE_FILE)
                    .withDescription("My description")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("draft")
                    .withProperties(properties)
                    .build();

                const current_folder = new FolderBuilder(456).build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    false,
                );

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

                const item = new ItemBuilder(123)
                    .withTitle("My file")
                    .withType(TYPE_FILE)
                    .withDescription("n")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("none")
                    .build();

                const item_to_update = new ItemBuilder(123)
                    .withTitle("My new title")
                    .withType(TYPE_FILE)
                    .withDescription("My description")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("draft")
                    .build();

                const current_folder = new FolderBuilder(456).build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    false,
                );

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
                const item = new ItemBuilder(123)
                    .withTitle("My embedded file")
                    .withType(TYPE_EMBEDDED)
                    .withDescription("nop")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("none")
                    .build();

                const properties: Array<Property> = [];
                const item_to_update = new ItemBuilder(123)
                    .withTitle("My new embedded  title")
                    .withType(TYPE_EMBEDDED)
                    .withDescription("My description")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("draft")
                    .withProperties(properties)
                    .build();

                const current_folder = new FolderBuilder(456).build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    false,
                );

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
                const item = new ItemBuilder(123)
                    .withTitle("My link")
                    .withType(TYPE_LINK)
                    .withDescription("ui")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("none")
                    .build();

                const properties: Array<Property> = [];
                const item_to_update = new ItemBuilder(123)
                    .withTitle("My new link title")
                    .withType(TYPE_LINK)
                    .withDescription("My link description")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("draft")
                    .withProperties(properties)
                    .build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                const current_folder = new FolderBuilder(456).build();

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    false,
                );

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
                const item = new ItemBuilder(123)
                    .withTitle("My wiki")
                    .withType(TYPE_WIKI)
                    .withDescription("on")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("none")
                    .build();

                const properties: Array<Property> = [];
                const item_to_update = new ItemBuilder(123)
                    .withTitle("My new wiki title")
                    .withType(TYPE_WIKI)
                    .withDescription("My wiki description")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("approved")
                    .withProperties(properties)
                    .build();

                const current_folder = new FolderBuilder(456).build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    false,
                );

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
                const item = new ItemBuilder(123)
                    .withTitle("My empty")
                    .withType(TYPE_EMPTY)
                    .withDescription("on")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("none")
                    .build();

                const properties: Array<Property> = [];
                const item_to_update = new ItemBuilder(123)
                    .withTitle("My new empty title")
                    .withType(TYPE_EMPTY)
                    .withDescription("My empty description")
                    .withOwner(new UserBuilder(102).build())
                    .withStatus("rejected")
                    .withProperties(properties)
                    .build();

                const current_folder = new FolderBuilder(456).build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    false,
                );

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
                    commit: vi.fn(),
                    dispatch: vi.fn(),
                } as unknown as ActionContext<RootState, RootState>;

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

                const current_folder = new FolderBuilder(456).build();

                getItem.mockReturnValue(Promise.resolve(item_to_update));

                await document_properties.updateProperties(
                    context,
                    item,
                    item_to_update,
                    current_folder,
                    true,
                );

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
                commit: vi.fn(),
                dispatch: vi.fn(),
            } as unknown as ActionContext<RootState, RootState>;

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

            await document_properties.updateProperties(
                context,
                item,
                item_to_update,
                current_folder,
                false,
            );

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

            await document_properties.updateProperties(
                context,
                item,
                item_to_update,
                current_folder,
                false,
            );

            expect(emitter.emit).toHaveBeenCalledWith("item-properties-have-just-been-updated");
            expect(context.commit).toHaveBeenCalledWith("replaceCurrentFolder", item_to_update, {
                root: true,
            });
            expect(context.dispatch).toHaveBeenCalledWith("loadFolder", current_folder.id, {
                root: true,
            });
        });
    });

    describe("loadProjectProperties", () => {
        let getProjectProperties: MockInstance;

        beforeEach(() => {
            getProjectProperties = vi.spyOn(properties_rest_querier, "getProjectProperties");
            vi.clearAllMocks();
        });

        it(`load project properties definition`, async () => {
            const properties = [
                new PropertyBuilder().withShortName("text").withType("text").build(),
            ];

            getProjectProperties.mockReturnValue(okAsync(properties));

            const result = await document_properties.loadProjectProperties(context, 102);

            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(null)).toStrictEqual(properties);
        });

        it("Handle error when properties project load fails", async () => {
            getProjectProperties.mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));

            await document_properties.loadProjectProperties(context, 102);

            expect(context.dispatch).toHaveBeenCalled();
        });
    });
});
