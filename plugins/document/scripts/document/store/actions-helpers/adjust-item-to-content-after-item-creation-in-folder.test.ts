/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import * as rest_querier from "../../api/rest-querier";
import { adjustItemToContentAfterItemCreationInAFolder } from "./adjust-item-to-content-after-item-creation-in-folder";
import * as flag_item_as_created from "./flag-item-as-created";
import type { ActionContext } from "vuex";
import type { Folder, State } from "../../type";

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

        await adjustItemToContentAfterItemCreationInAFolder(
            context,
            parent,
            current_folder,
            item_id
        );

        expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", created_item);
        expect(flagItemAsCreated).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith("addDocumentToFoldedFolder", [
            parent,
            created_item,
            false,
        ]);
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            created_item
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

        await adjustItemToContentAfterItemCreationInAFolder(
            context,
            parent,
            current_folder,
            item_id
        );

        expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", created_item);
        expect(flagItemAsCreated).toHaveBeenCalled();
        expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            created_item
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

        await adjustItemToContentAfterItemCreationInAFolder(
            context,
            parent,
            current_folder,
            item_id
        );

        expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", created_item);
        expect(flagItemAsCreated).toHaveBeenCalled();
        expect(context.commit).not.toHaveBeenCalledWith("addDocumentToFoldedFolder");
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            created_item
        );
    });
});
