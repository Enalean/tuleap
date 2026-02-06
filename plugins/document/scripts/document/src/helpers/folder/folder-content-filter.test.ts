/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
import { TYPE_FILE } from "../../constants";
import type { Item, State } from "../../type";
import { FolderBuilder } from "../../../tests/builders/FolderBuilder";
import {
    findNearestByTitle,
    getDocumentSiblings,
    getFolderSiblings,
    getLastItemInSubtree,
    insertBeforeItem,
    insertAfterItem,
    getLastOrThrow,
    insertAfterLastInSubtree,
} from "./folder-content-filter";

describe(`Folder content filter - given the following file system
    ROOT (id: 999)
    ├── folder_a (id: 1)
    └── folder_c (id: 3)
        └── subfolder_c (id: 300)
        └── subfolder_file_c (id: 301)
    └── file_b (id: 2)
    └── file_d (id: 4)

    ORPHAN TREE (parent_id: 1267216)
    └── A10 (id: 10)
    └── A20 (id: 20)
    └── A30 (id: 30)
    └── A40 (id: 40)
    `, () => {
    const root_folder = new FolderBuilder(999)
        .withTitle("Project Documentation")
        .withLevel(0)
        .build();

    const folder_a = new FolderBuilder(1)
        .withTitle("folder a")
        .withLevel(1)
        .withParentId(root_folder.id)
        .build();
    const file_b = new ItemBuilder(2)
        .withType(TYPE_FILE)
        .withTitle("file b")
        .withLevel(1)
        .withParentId(root_folder.id)
        .build();
    const folder_c = new FolderBuilder(3)
        .withTitle("folder c")
        .withParentId(root_folder.id)
        .withLevel(1)
        .build();
    const file_d = new ItemBuilder(4)
        .withType(TYPE_FILE)
        .withTitle("file d")
        .withParentId(root_folder.id)
        .withLevel(1)
        .build();

    const subfolder_c = new FolderBuilder(300)
        .withTitle("subfolder c")
        .withParentId(folder_c.id)
        .withLevel(2)
        .build();
    const subfile_c = new ItemBuilder(301)
        .withType(TYPE_FILE)
        .withTitle("subfile c")
        .withLevel(2)
        .withParentId(folder_c.id)
        .build();

    const A10 = new FolderBuilder(10).withTitle("A10").withParentId(1267216).build();
    const A20 = new ItemBuilder(20)
        .withType(TYPE_FILE)
        .withTitle("A20")
        .withParentId(1267216)
        .build();
    const A30 = new FolderBuilder(30).withTitle("A30").withParentId(1267216).build();
    const A40 = new ItemBuilder(40)
        .withType(TYPE_FILE)
        .withTitle("A40")
        .withParentId(1267216)
        .build();

    const folder_not_in_tree_id = 6565;

    let all_tree: Array<Item> = [];

    beforeEach(() => {
        all_tree = [folder_a, folder_c, subfolder_c, subfile_c, file_b, file_d, A10, A20, A30, A40];
    });

    describe("getFolderSiblings - ", () => {
        it("returns only folder items with the same parent_id", () => {
            const state: State = {
                folder_content: all_tree,
            } as State;
            const result = getFolderSiblings(state, root_folder.id);
            expect(result).toEqual([folder_a, folder_c]);
        });
        it("returns an empty array when no folders match the parent_id", () => {
            const state: State = { folder_content: all_tree } as State;
            const result = getFolderSiblings(state, folder_not_in_tree_id);
            expect(result).toEqual([]);
        });
    });

    describe("getDocumentSiblings - ", () => {
        it("returns only document items with the same parent_id", () => {
            const state: State = { folder_content: all_tree } as State;
            const result = getDocumentSiblings(state, root_folder.id);
            expect(result).toEqual([file_b, file_d]);
        });
        it("returns an empty array when no documents match the parent_id", () => {
            const state: State = { folder_content: all_tree } as State;
            const result = getDocumentSiblings(state, folder_not_in_tree_id);
            expect(result).toEqual([]);
        });
    });

    describe("findNearestByTitle - ", () => {
        it("returns the nearest title when item is in middle of folder content", () => {
            const sub_tree = [folder_a, file_d];
            const new_item = new ItemBuilder(99).withTitle("file b").build();
            const result = findNearestByTitle(sub_tree, new_item);
            expect(result).toEqual(folder_a);
        });

        it("returns undefined when item is at the end of the folder", () => {
            const new_item = new ItemBuilder(99).withTitle("Zulu").build();
            const result = findNearestByTitle(all_tree, new_item);
            expect(result).toBeUndefined();
        });
        it("respects numeric sorting (e.g. 'A 2' < 'A 3')", () => {
            const new_item = new ItemBuilder(99).withTitle("A30").build();
            const result = findNearestByTitle([A10, A20, A30, A40], new_item);
            expect(result).toEqual(A30);
        });
        it("returns the first item when element is at the beginning of the folder", () => {
            const new_item = new ItemBuilder(99).withTitle("A").build();
            const result = findNearestByTitle(all_tree, new_item);
            expect(result).toEqual(folder_a);
        });
    });

    describe("getLastItemInSubtree - ", () => {
        it("returns the folder itself when it is not found in state", () => {
            const state: State = { folder_content: [subfile_c] } as State;
            expect(getLastItemInSubtree(state, folder_a)).toBe(folder_a);
        });

        it("returns the folder itself when it has no children", () => {
            const state: State = { folder_content: all_tree } as State;

            expect(getLastItemInSubtree(state, folder_a)).toBe(folder_a);
        });

        it("returns the last item in a simple subtree", () => {
            const state: State = { folder_content: all_tree } as State;

            expect(getLastItemInSubtree(state, folder_c)).toBe(subfile_c);
        });
    });

    describe("insertBeforeItem - ", () => {
        it("inserts the item before the target", () => {
            const sub_tree = [folder_a, file_b, folder_c];

            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(99).build();

            insertBeforeItem(state, file_b, new_item);

            expect(state.folder_content.map((i) => i.id)).toEqual([
                folder_a.id,
                99,
                file_b.id,
                folder_c.id,
            ]);
        });

        it("inserts at the beginning when target is the first item", () => {
            const sub_tree = [folder_a, file_b, folder_c];
            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(50).build();

            insertBeforeItem(state, folder_a, new_item);

            expect(state.folder_content.map((i) => i.id)).toEqual([
                50,
                folder_a.id,
                file_b.id,
                folder_c.id,
            ]);
        });

        it("inserts before the last item", () => {
            const sub_tree = [folder_a, file_b, folder_c];
            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(77).build();

            insertBeforeItem(state, folder_c, new_item);

            expect(state.folder_content.map((i) => i.id)).toEqual([
                folder_a.id,
                file_b.id,
                77,
                folder_c.id,
            ]);
        });
    });

    describe("insertAfterItem - ", () => {
        it("inserts the item after the target", () => {
            const sub_tree = [folder_a, file_b, folder_c];

            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(99).build();

            insertAfterItem(state, file_b, new_item);

            expect(state.folder_content.map((i) => i.id)).toEqual([
                folder_a.id,
                file_b.id,
                99,
                folder_c.id,
            ]);
        });

        it("inserts at the end when target is the last item", () => {
            const sub_tree = [folder_a, file_b, folder_c];
            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(50).build();

            insertAfterItem(state, folder_c, new_item);

            expect(state.folder_content.map((i) => i.id)).toEqual([
                folder_a.id,
                file_b.id,
                folder_c.id,
                50,
            ]);
        });

        it("inserts after the first item", () => {
            const sub_tree = [folder_a, file_b, folder_c];
            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(77).build();

            insertAfterItem(state, folder_a, new_item);

            expect(state.folder_content.map((i) => i.id)).toEqual([
                folder_a.id,
                77,
                file_b.id,
                folder_c.id,
            ]);
        });
    });
    describe("getLastOrThrow - ", () => {
        it("returns the last item of array list", () => {
            expect(getLastOrThrow(all_tree)).toBe(A40);
        });
        it("throws when array is empty", () => {
            expect(() => getLastOrThrow([])).toThrow("Expected a non-empty array");
        });
    });

    describe("insertAfterLastInSubtree - ", () => {
        it("inserts after the last item in folder_c subtree", () => {
            const sub_tree = [folder_a, folder_c, subfolder_c, subfile_c, file_d, A10];
            const state: State = { folder_content: sub_tree } as State;
            const new_item = new ItemBuilder(999)
                .withType(TYPE_FILE)
                .withTitle("ZZZ - tile does not matter")
                .withLevel(2)
                .withParentId(folder_c.id)
                .build();
            insertAfterLastInSubtree(state, folder_c, new_item);
            expect(state.folder_content.map((i) => i.id)).toEqual([
                folder_a.id,
                folder_c.id,
                subfolder_c.id,
                subfile_c.id,
                999,
                file_d.id,
                A10.id,
            ]);
        });
    });
});
