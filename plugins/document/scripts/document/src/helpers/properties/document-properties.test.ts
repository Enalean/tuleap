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
import type { ListValue, Property, RootState } from "../../type";
import type { ActionContext } from "vuex";
import * as rest_querier from "../../api/rest-querier";
import { FolderBuilder } from "../../../tests/builders/FolderBuilder";
import * as properties_rest_querier from "../../api/properties-rest-querier";
import emitter from "../../helpers/emitter";
import { UserBuilder } from "../../../tests/builders/UserBuilder";
import { PropertyBuilder } from "../../../tests/builders/PropertyBuilder";

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
});
