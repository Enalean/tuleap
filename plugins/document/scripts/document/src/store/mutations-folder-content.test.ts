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

import { beforeEach, describe, expect, it } from "vitest";
import * as mutations from "./mutations-folder-content";
import { StateBuilder } from "../../tests/builders/StateBuilder";
import { ItemBuilder } from "../../tests/builders/ItemBuilder";
import { FakeItemBuilder } from "../../tests/builders/FakeItemBuilder";
import type { FolderContentItem, State } from "../type";
import { TYPE_FILE } from "../constants";
import { FolderBuilder } from "../../tests/builders/FolderBuilder";

describe("Store mutations", () => {
    describe("foldFolderContent", () => {
        /**
         *         Folder structure
         *
         *              __0__
         *             /     \
         *           _30      31
         *          /   \
         *        _32    33-
         *       /   \   / \ \
         *    _34    35 40 41 42
         *   /  \   / \     \
         *  36  37 38 39     43
         */
        it("stores the ids of the items to hide and updates which folders do fold which items.", () => {
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(30).withParentId(0).build(),
                    new ItemBuilder(32).withParentId(30).build(),
                    new ItemBuilder(34).withParentId(32).build(),
                    new ItemBuilder(36).withParentId(34).build(),
                    new ItemBuilder(37).withParentId(34).build(),
                    new ItemBuilder(35).withParentId(32).build(),
                    new ItemBuilder(38).withParentId(35).build(),
                    new ItemBuilder(39).withParentId(35).build(),
                    new ItemBuilder(33).withParentId(30).build(),
                    new ItemBuilder(40).withParentId(33).build(),
                    new ItemBuilder(41).withParentId(33).build(),
                    new ItemBuilder(43).withParentId(41).build(),
                    new ItemBuilder(42).withParentId(33).build(),
                    new ItemBuilder(31).withParentId(0).build(),
                ])
                .build();

            mutations.foldFolderContent(state, 35);
            expect(state.folded_items_ids).toEqual([38, 39]);
            expect(state.folded_by_map).toEqual({
                35: [38, 39],
            });

            mutations.foldFolderContent(state, 34);
            expect(state.folded_items_ids).toEqual([38, 39, 36, 37]);
            expect(state.folded_by_map).toEqual({
                35: [38, 39],
                34: [36, 37],
            });

            mutations.foldFolderContent(state, 32);
            expect(state.folded_items_ids).toEqual([38, 39, 36, 37, 34, 35]);
            expect(state.folded_by_map).toEqual({
                35: [38, 39],
                34: [36, 37],
                32: [34, 35],
            });
        });
    });

    describe("unfoldFolderContent", () => {
        it("remove all the ids of the children and grand children of a given from state.folded_items_ids.", () => {
            const state = new StateBuilder()
                .withFoldedByMap({
                    32: [34, 35],
                    34: [36, 37],
                    35: [38, 39],
                })
                .withFoldedItemsIds([34, 36, 37, 35, 38, 39])
                .withFolderContent([
                    new ItemBuilder(30).withParentId(0).build(),
                    new ItemBuilder(32).withParentId(30).build(),
                    new ItemBuilder(34).withParentId(32).build(),
                    new ItemBuilder(36).withParentId(34).build(),
                    new ItemBuilder(37).withParentId(34).build(),
                    new ItemBuilder(35).withParentId(32).build(),
                    new ItemBuilder(38).withParentId(35).build(),
                    new ItemBuilder(39).withParentId(35).build(),
                    new ItemBuilder(33).withParentId(30).build(),
                    new ItemBuilder(40).withParentId(33).build(),
                    new ItemBuilder(41).withParentId(33).build(),
                    new ItemBuilder(43).withParentId(41).build(),
                    new ItemBuilder(42).withParentId(33).build(),
                    new ItemBuilder(31).withParentId(0).build(),
                ])
                .build();

            mutations.unfoldFolderContent(state, 32);
            expect(state.folded_items_ids).toEqual([36, 37, 38, 39]);
            expect(state.folded_by_map).toEqual({
                34: [36, 37],
                35: [38, 39],
            });

            mutations.unfoldFolderContent(state, 34);
            expect(state.folded_items_ids).toEqual([38, 39]);
            expect(state.folded_by_map).toEqual({
                35: [38, 39],
            });

            mutations.unfoldFolderContent(state, 35);
            expect(state.folded_items_ids).toEqual([]);
            expect(state.folded_by_map).toEqual({});
        });
    });

    describe(`addJustCreatedItemToFolderContent`, () => {
        let state: State;
        const root_folder = new FolderBuilder(7676).withTitle("A - Project Documentation").build();

        beforeEach(() => {
            state = { folder_content: [root_folder] as Array<FolderContentItem> } as State;
        });

        function checkThatFolderContentStateIsCorrect(
            expected_folder_content: Array<string>,
        ): void {
            const expected_folder_content_by_title = state.folder_content.map(
                (i) => `${i.title} (id: ${i.id}) (parent ${i.parent_id}) (level: ${i.level})`,
            );

            expect(expected_folder_content_by_title).toEqual(expected_folder_content);
        }

        describe(`Given elements are added inside a folder
        Then the folder_content respect sort structure and indentation level
        id are the order where element are inserted in tree, tree bellow is the expected final result

        ROOT (id: 7676)
        ├── an other folder  (id: 4)
        ├── folder_a (id: 2) - working folder - is opened
            └── folder a1 (id: 9)
                └── subfile A11 (id: 10)
            └── folder a2 (id: 5)
                └── subfolder a22 (id: 8)
                └── subfile a21 (id: 7)
            └── folder b (id: 11)
            └── file a (id: 12)
            └── file b (id: 3)
            └── file c (id: 13)
        ├── zan other folder  (id: 6)
        ├── a file  (id: 1)
        `, () => {
            it(`Given folder is updated, it respects the order for files and folders`, () => {
                const a_file = new ItemBuilder(1)
                    .withType(TYPE_FILE)
                    .withTitle("a file")
                    .withParentId(root_folder.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: a_file,
                    parent: root_folder,
                });

                const folder_a = new FolderBuilder(2)
                    .withTitle("Folder A")
                    .withParentId(root_folder.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: folder_a,
                    parent: root_folder,
                });

                const file_b = new ItemBuilder(3)
                    .withType(TYPE_FILE)
                    .withTitle("File B")
                    .withParentId(folder_a.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: file_b,
                    parent: folder_a,
                });

                const an_other_folder = new FolderBuilder(4)
                    .withTitle("An other folder")
                    .withParentId(root_folder.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: an_other_folder,
                    parent: root_folder,
                });

                const folder_a2 = new FolderBuilder(5)
                    .withTitle("Folder A2")
                    .withParentId(folder_a.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: folder_a2,
                    parent: folder_a,
                });

                const z_an_other_folder = new FolderBuilder(6)
                    .withTitle("Z An Other folder")
                    .withParentId(root_folder.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: z_an_other_folder,
                    parent: root_folder,
                });

                const subfile_a21 = new ItemBuilder(7)
                    .withType(TYPE_FILE)
                    .withTitle("subfile_a21")
                    .withParentId(folder_a2.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: subfile_a21,
                    parent: folder_a2,
                });

                const subfolder_a22 = new FolderBuilder(8)
                    .withTitle("subfolder_a22")
                    .withParentId(folder_a2.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: subfolder_a22,
                    parent: folder_a2,
                });

                const folder_a1 = new FolderBuilder(9)
                    .withTitle("folder A1")
                    .withParentId(folder_a.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: folder_a1,
                    parent: folder_a,
                });

                const subfile_a11 = new ItemBuilder(10)
                    .withType(TYPE_FILE)
                    .withTitle("subfile_a11")
                    .withParentId(folder_a1.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: subfile_a11,
                    parent: folder_a1,
                });

                const folder_b = new FolderBuilder(11)
                    .withTitle("folder_b")
                    .withParentId(folder_a.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: folder_b,
                    parent: folder_a,
                });

                const file_a = new ItemBuilder(12)
                    .withType(TYPE_FILE)
                    .withTitle("File A")
                    .withParentId(folder_a.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: file_a,
                    parent: folder_a,
                });

                const file_c = new ItemBuilder(13)
                    .withType(TYPE_FILE)
                    .withTitle("File C")
                    .withParentId(folder_a.id)
                    .build();
                mutations.addJustCreatedItemToFolderContent(state, {
                    new_item: file_c,
                    parent: folder_a,
                });

                checkThatFolderContentStateIsCorrect([
                    `${root_folder.title} (id: ${root_folder.id}) (parent ${root_folder.parent_id}) (level: ${root_folder.level})`,
                    `${an_other_folder.title} (id: ${an_other_folder.id}) (parent ${an_other_folder.parent_id}) (level: ${an_other_folder.level})`,
                    `${folder_a.title} (id: ${folder_a.id}) (parent ${folder_a.parent_id}) (level: ${folder_a.level})`,
                    `${folder_a1.title} (id: ${folder_a1.id}) (parent ${folder_a1.parent_id}) (level: ${folder_a1.level})`,
                    `${subfile_a11.title} (id: ${subfile_a11.id}) (parent ${subfile_a11.parent_id}) (level: ${subfile_a11.level})`,
                    `${folder_a2.title} (id: ${folder_a2.id}) (parent ${folder_a2.parent_id}) (level: ${folder_a2.level})`,
                    `${subfolder_a22.title} (id: ${subfolder_a22.id}) (parent ${subfolder_a22.parent_id}) (level: ${subfolder_a22.level})`,
                    `${subfile_a21.title} (id: ${subfile_a21.id}) (parent ${subfile_a21.parent_id}) (level: ${subfile_a21.level})`,
                    `${folder_b.title} (id: ${folder_b.id}) (parent ${folder_b.parent_id}) (level: ${folder_b.level})`,
                    `${file_a.title} (id: ${file_a.id}) (parent ${file_a.parent_id}) (level: ${file_a.level})`,
                    `${file_b.title} (id: ${file_b.id}) (parent ${file_b.parent_id}) (level: ${file_b.level})`,
                    `${file_c.title} (id: ${file_c.id}) (parent ${file_c.parent_id}) (level: ${file_c.level})`,
                    `${z_an_other_folder.title} (id: ${z_an_other_folder.id}) (parent ${z_an_other_folder.parent_id}) (level: ${z_an_other_folder.level})`,
                    `${a_file.title} (id: ${a_file.id}) (parent ${a_file.parent_id}) (level: ${a_file.level})`,
                ]);
            });
        });
    });

    describe("replaceUploadingFileWithActualFile", () => {
        it("should replace the fake item by the actual item in the folder content and current previewed item", () => {
            const fake_item = new FakeItemBuilder(46)
                .withTitle("toto.txt")
                .withParentId(42)
                .withType("file")
                .withFileType("plain/text")
                .build();

            const item = new ItemBuilder(46)
                .withTitle("toto.txt")
                .withParentId(42)
                .withType("file")
                .build();

            const actual_file = new ItemBuilder(46)
                .withParentId(42)
                .withLevel(3)
                .withType("file")
                .withTitle("toto.txt")
                .build();

            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("tata.txt")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("titi.txt")
                        .build(),
                    item,
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("tutu.txt")
                        .build(),
                ])
                .withCurrentlyPreviewItem(item)
                .build();

            mutations.replaceUploadingFileWithActualFile(state, [fake_item, actual_file]);

            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("tata.txt")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("titi.txt")
                    .build(),
                actual_file,
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("tutu.txt")
                    .build(),
            ]);
            expect(state.currently_previewed_item).toEqual(actual_file);
        });

        it("does not replace currently_previewed_item if it's not the uploaded file", () => {
            const fake_item = new FakeItemBuilder(12)
                .withTitle("toto.txt")
                .withParentId(42)
                .withType("file")
                .withFileType("plain/text")
                .build();

            const item = new ItemBuilder(46)
                .withTitle("toto.txt")
                .withParentId(42)
                .withType("file")
                .build();

            const actual_file = new ItemBuilder(12)
                .withParentId(42)
                .withLevel(3)
                .withType("file")
                .withTitle("toto.txt")
                .build();

            const state = new StateBuilder()
                .withFolderContent([item])
                .withCurrentlyPreviewItem(item)
                .build();

            mutations.replaceUploadingFileWithActualFile(state, [fake_item, actual_file]);

            expect(state.folder_content).toEqual([item]);
            expect(state.currently_previewed_item).toEqual(item);
        });
    });

    describe("removeItemFromFolderContent", () => {
        it("should remove the item from the folder content", () => {
            const item = new ItemBuilder(46).withTitle("toto.txt").withParentId(42).build();

            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("tata.txt")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("titi.txt")
                        .build(),
                    item,
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("tutu.txt")
                        .build(),
                ])
                .build();

            mutations.removeItemFromFolderContent(state, item);

            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("tata.txt")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("titi.txt")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("tutu.txt")
                    .build(),
            ]);
        });

        it("should not remove any element if id is not found in array", () => {
            const item = new ItemBuilder(46).withTitle("toto.txt").withParentId(42).build();

            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("tata.txt")
                        .build(),
                ])
                .build();

            mutations.removeItemFromFolderContent(state, item);

            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("tata.txt")
                    .build(),
            ]);
        });

        it("should remove all its children (and subfolders' children) if the item is a folder and clear the folding maps", () => {
            const folder_item = new ItemBuilder(46)
                .withTitle("trash folder")
                .withParentId(0)
                .withType("folder")
                .build();

            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(44)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("sibling folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(44)
                        .withLevel(2)
                        .withType("wiki")
                        .withTitle("titi.txt")
                        .build(),
                    folder_item,
                    new ItemBuilder(47)
                        .withParentId(46)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("tata.txt")
                        .build(),
                    new ItemBuilder(48)
                        .withParentId(46)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("subfolder")
                        .build(),
                    new ItemBuilder(49)
                        .withParentId(46)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("tutu.txt")
                        .build(),
                ])
                .withFoldedItemsIds([45, 47, 49])
                .withFoldedByMap({
                    44: [45],
                    46: [47],
                    48: [49],
                })
                .build();

            mutations.removeItemFromFolderContent(state, folder_item);

            expect(state.folder_content).toEqual([
                new ItemBuilder(44)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("sibling folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(44)
                    .withLevel(2)
                    .withType("wiki")
                    .withTitle("titi.txt")
                    .build(),
            ]);

            expect(state.folded_items_ids).toEqual([45]);
            expect(state.folded_by_map).toEqual({ 44: [45] });
        });
    });

    describe("appendSubFolderContent", () => {
        const folder = new ItemBuilder(123)
            .withTitle("A sub-folder")
            .withLevel(3)
            .withType("folder")
            .withParentId(42)
            .build();

        const sub_item_1 = new ItemBuilder(1231)
            .withTitle("sub-item 1")
            .withParentId(folder.id)
            .withType("file")
            .build();
        const sub_item_2 = new ItemBuilder(1232)
            .withTitle("sub-item 1")
            .withParentId(folder.id)
            .withType("file")
            .build();
        const sub_item_3 = new ItemBuilder(1233)
            .withTitle("sub-item 1")
            .withParentId(folder.id)
            .withType("file")
            .build();

        it("should append the sub-items next to the parent in state.folder_content", () => {
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("tata.txt")
                        .build(),
                    folder,
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("titi.txt")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("tutu.txt")
                        .build(),
                ])
                .withFoldedItemsIds([])
                .withFoldedByMap({})
                .build();

            mutations.appendSubFolderContent(state, [
                folder.id,
                [sub_item_1, sub_item_2, sub_item_3],
            ]);

            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("tata.txt")
                    .build(),
                folder,
                sub_item_1,
                sub_item_2,
                sub_item_3,
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("titi.txt")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("tutu.txt")
                    .build(),
            ]);
        });

        it(`When the sub-folder is being folded by another folder
            Then the sub-items next should be placed next to the parent in state.folder_content
            And they should be marked as folded by the same folder than their parent.`, () => {
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("tata.txt")
                        .build(),
                    folder,
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("titi.txt")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("tutu.txt")
                        .build(),
                ])
                .withFoldedItemsIds([43, 44, 45, folder.id])
                .withFoldedByMap({
                    42: [43, 44, 45, folder.id],
                })
                .build();

            mutations.appendSubFolderContent(state, [
                folder.id,
                [sub_item_1, sub_item_2, sub_item_3],
            ]);

            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("tata.txt")
                    .build(),
                folder,
                sub_item_1,
                sub_item_2,
                sub_item_3,
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("titi.txt")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("file")
                    .withTitle("tutu.txt")
                    .build(),
            ]);

            expect(state.folded_items_ids).toEqual([
                43,
                44,
                45,
                folder.id,
                sub_item_1.id,
                sub_item_2.id,
                sub_item_3.id,
            ]);

            expect(state.folded_by_map).toEqual({
                42: [43, 44, 45, folder.id, sub_item_1.id, sub_item_2.id, sub_item_3.id],
            });
        });

        it(`When the parent folder is no longer defined (user quit loading before end of execution)
            Then the mutation must not be executed.`, () => {
            const state = new StateBuilder()
                .withFolderContent([])
                .withFoldedItemsIds([43, 44, 45, folder.id])
                .withFoldedByMap({
                    42: [43, 44, 45, folder.id],
                })
                .build();

            mutations.appendSubFolderContent(state, [
                folder.id,
                [sub_item_1, sub_item_2, sub_item_3],
            ]);

            expect(state.folder_content).toStrictEqual([]);

            expect(state.folded_items_ids).toStrictEqual([43, 44, 45, folder.id]);

            expect(state.folded_by_map).toStrictEqual({
                42: [43, 44, 45, folder.id],
            });
        });
    });
});
