/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { describe, expect, it } from "vitest";
import type { FakeItem, Folder, Item } from "../../type";
import { TYPE_EMBEDDED, TYPE_FOLDER } from "../../constants";
import { getParentFolder } from "./item-retriever";

describe("item-retriever", () => {
    it("Throw an error if parent item is not a folder", () => {
        const folder_content = [{ id: 1, type: TYPE_EMBEDDED } as Item];
        const current_folder = { id: 3, type: TYPE_FOLDER } as Folder;
        const fake_item = { parent_id: 1, type: TYPE_EMBEDDED } as FakeItem;

        expect(() => getParentFolder(folder_content, fake_item, current_folder)).toThrow();
    });

    it("When parent is not found, then it returns current folder", () => {
        const parent = { id: 2, type: TYPE_FOLDER } as Item;
        const folder_content = [parent];
        const current_folder = { id: 3, type: TYPE_FOLDER } as Folder;
        const fake_item = { parent_id: 1, type: TYPE_EMBEDDED } as FakeItem;

        expect(getParentFolder(folder_content, fake_item, current_folder)).toEqual(current_folder);
    });

    it("Returns parent folder", () => {
        const parent = { id: 1, type: TYPE_FOLDER } as Item;
        const folder_content = [parent];
        const current_folder = { id: 3, type: TYPE_FOLDER } as Folder;
        const fake_item = { parent_id: 1, type: TYPE_EMBEDDED } as FakeItem;

        expect(getParentFolder(folder_content, fake_item, current_folder)).toEqual(parent);
    });
});
