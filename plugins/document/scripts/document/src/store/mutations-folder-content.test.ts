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

import { describe, expect, it } from "vitest";
import * as mutations from "./mutations-folder-content";
import { StateBuilder } from "../../tests/builders/StateBuilder";
import { ItemBuilder } from "../../tests/builders/ItemBuilder";
import { FakeItemBuilder } from "../../tests/builders/FakeItemBuilder";

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

    describe("addJustCreatedItemToFolderContent", () => {
        it("set the level of the new document according to its parent one", () => {
            const item = new ItemBuilder(66)
                .withParentId(42)
                .withType("wiki")
                .withTitle("Document")
                .build();
            const folder = new ItemBuilder(42)
                .withParentId(0)
                .withType("folder")
                .withTitle("Folder")
                .withLevel(2)
                .build();
            const state = new StateBuilder().withFolderContent([folder]).build();

            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([folder, item]);
        });
        it("default to level=0 if parent is not found (should not happen)", () => {
            const item = new ItemBuilder(66)
                .withParentId(42)
                .withType("wiki")
                .withTitle("Document")
                .build();
            const folder = new ItemBuilder(101)
                .withParentId(0)
                .withType("folder")
                .withTitle("Folder")
                .withLevel(2)
                .build();
            const state = new StateBuilder().withFolderContent([folder]).build();

            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([folder, item]);
        });
        it("inserts DOCUMENT by respecting the natural sort order", () => {
            const item = new ItemBuilder(66)
                .withParentId(42)
                .withType("wiki")
                .withTitle("A.2.x")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.1")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.10")
                        .build(),
                ])
                .build();

            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.1")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.2.x")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.10")
                    .build(),
            ]);
        });
        it("inserts DOCUMENT by respecting the natural sort order, and AFTER folders", () => {
            const item = new ItemBuilder(66)
                .withParentId(42)
                .withType("wiki")
                .withTitle("A.2.x")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("A.1")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("A.10")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();

            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("A.1")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("A.10")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.2.x")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
            ]);
        });
        it("inserts FOLDER by respecting the natural sort order, and BEFORE items", () => {
            const folder = new ItemBuilder(66)
                .withParentId(42)
                .withType("folder")
                .withTitle("D folder")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("B folder")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("C folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, folder);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("B folder")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("C folder")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("D folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
            ]);
        });
        it("inserts FOLDER by respecting the natural sort order, and at the right level", () => {
            const folder = new ItemBuilder(66)
                .withParentId(43)
                .withType("folder")
                .withTitle("Z folder")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("B folder")
                        .build(),
                    new ItemBuilder(46)
                        .withParentId(43)
                        .withLevel(4)
                        .withType("wiki")
                        .withTitle("B.1")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("D folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, folder);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("B folder")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("folder")
                    .withTitle("Z folder")
                    .build(),
                new ItemBuilder(46)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("wiki")
                    .withTitle("B.1")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("D folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
            ]);
        });
        it("inserts DOCUMENT by respecting the natural sort order, and at the right level", () => {
            const item = new ItemBuilder(66)
                .withParentId(43)
                .withType("empty")
                .withTitle("zzzzempty")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("B folder")
                        .build(),
                    new ItemBuilder(46)
                        .withParentId(43)
                        .withLevel(4)
                        .withType("wiki")
                        .withTitle("B.1")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("D folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("B folder")
                    .build(),
                new ItemBuilder(46)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("wiki")
                    .withTitle("B.1")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("empty")
                    .withTitle("zzzzempty")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("D folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
            ]);
        });
        it("inserts DOCUMENT by respecting the natural sort order, at the end of the folder", () => {
            const item = new ItemBuilder(66)
                .withParentId(42)
                .withType("empty")
                .withTitle("zzzzempty")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("B folder")
                        .build(),
                    new ItemBuilder(46)
                        .withParentId(43)
                        .withLevel(4)
                        .withType("wiki")
                        .withTitle("B.1")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("D folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("B folder")
                    .build(),
                new ItemBuilder(46)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("wiki")
                    .withTitle("B.1")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("D folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("empty")
                    .withTitle("zzzzempty")
                    .build(),
            ]);
        });
        it("inserts FOLDER by respecting the natural sort order, at the end of the folder", () => {
            const folder = new ItemBuilder(66)
                .withParentId(43)
                .withType("folder")
                .withTitle("zzzzfolder")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("B folder")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("D folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, folder);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("B folder")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("folder")
                    .withTitle("zzzzfolder")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("D folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
            ]);
        });
        it("inserts DOCUMENT by respecting the natural sort order, at the end of the folder 2", () => {
            const item = new ItemBuilder(66)
                .withParentId(43)
                .withType("empty")
                .withTitle("zzzzDOCUMENT")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(2)
                        .withType("folder")
                        .withTitle("Folder")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("B folder")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("folder")
                        .withTitle("D folder")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("wiki")
                        .withTitle("A.11")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(2)
                    .withType("folder")
                    .withTitle("Folder")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("B folder")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(43)
                    .withLevel(4)
                    .withType("empty")
                    .withTitle("zzzzDOCUMENT")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("folder")
                    .withTitle("D folder")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(3)
                    .withType("wiki")
                    .withTitle("A.11")
                    .build(),
            ]);
        });
        it("inserts a FOLDER at the right place, after the last children of its nearest sibling", () => {
            const item = new ItemBuilder(66)
                .withParentId(0)
                .withType("folder")
                .withTitle("B")
                .build();
            const state = new StateBuilder()
                .withFolderContent([
                    new ItemBuilder(42)
                        .withParentId(0)
                        .withLevel(0)
                        .withType("folder")
                        .withTitle("A")
                        .build(),
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(1)
                        .withType("folder")
                        .withTitle("A.A")
                        .build(),
                    new ItemBuilder(45)
                        .withParentId(42)
                        .withLevel(1)
                        .withType("wiki")
                        .withTitle("A kiwi")
                        .build(),
                    new ItemBuilder(44)
                        .withParentId(0)
                        .withLevel(0)
                        .withType("folder")
                        .withTitle("C")
                        .build(),
                ])
                .build();
            mutations.addJustCreatedItemToFolderContent(state, item);
            expect(state.folder_content).toEqual([
                new ItemBuilder(42)
                    .withParentId(0)
                    .withLevel(0)
                    .withType("folder")
                    .withTitle("A")
                    .build(),
                new ItemBuilder(43)
                    .withParentId(42)
                    .withLevel(1)
                    .withType("folder")
                    .withTitle("A.A")
                    .build(),
                new ItemBuilder(45)
                    .withParentId(42)
                    .withLevel(1)
                    .withType("wiki")
                    .withTitle("A kiwi")
                    .build(),
                new ItemBuilder(66)
                    .withParentId(0)
                    .withLevel(0)
                    .withType("folder")
                    .withTitle("B")
                    .build(),
                new ItemBuilder(44)
                    .withParentId(0)
                    .withLevel(0)
                    .withType("folder")
                    .withTitle("C")
                    .build(),
            ]);
        });
        it("Given newly created items, then they should be inserted at the right place", () => {
            const folder_a = new ItemBuilder(10)
                .withTitle("A")
                .withType("folder")
                .withParentId(42)
                .withLevel(0)
                .build();
            const folder_b = new ItemBuilder(11)
                .withTitle("B")
                .withType("folder")
                .withParentId(42)
                .withLevel(0)
                .build();
            const doc_a = new ItemBuilder(12)
                .withTitle("A")
                .withType("wiki")
                .withParentId(42)
                .withLevel(0)
                .build();
            const doc_b = new ItemBuilder(13)
                .withTitle("B")
                .withType("wiki")
                .withParentId(42)
                .withLevel(0)
                .build();
            const sub_folder_a = new ItemBuilder(14)
                .withTitle("A")
                .withType("folder")
                .withParentId(folder_a.id)
                .withLevel(0)
                .build();
            const sub_folder_b = new ItemBuilder(15)
                .withTitle("B")
                .withType("folder")
                .withParentId(folder_a.id)
                .withLevel(0)
                .build();
            const sub_doc_a = new ItemBuilder(16)
                .withTitle("A")
                .withType("wiki")
                .withParentId(folder_a.id)
                .withLevel(0)
                .build();
            const sub_doc_b = new ItemBuilder(17)
                .withTitle("B")
                .withType("wiki")
                .withParentId(folder_a.id)
                .withLevel(0)
                .build();
            const doc_to_add = new ItemBuilder(66)
                .withTitle("A1")
                .withType("wiki")
                .withParentId(42)
                .build();
            const doc_added = { ...doc_to_add, level: 0 };
            const doc_to_add_in_folder_a = { ...doc_to_add, parent_id: folder_a.id };
            const doc_added_in_folder_a = { ...doc_to_add_in_folder_a, level: 1 };
            const folder_to_add = new ItemBuilder(69)
                .withTitle("A1")
                .withType("folder")
                .withParentId(42)
                .build();
            const folder_added = { ...folder_to_add, level: 0 };
            const folder_to_add_in_folder_a = { ...folder_to_add, parent_id: folder_a.id };
            const folder_added_in_folder_a = { ...folder_to_add_in_folder_a, level: 1 };
            const map = new Map([
                [
                    {
                        item: doc_to_add,
                        folder_content: [],
                    },
                    [doc_added],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [doc_a],
                    },
                    [doc_a, doc_added],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [doc_b],
                    },
                    [doc_added, doc_b],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [doc_a, doc_b],
                    },
                    [doc_a, doc_added, doc_b],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [folder_a],
                    },
                    [folder_a, doc_added],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [folder_b],
                    },
                    [folder_b, doc_added],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [folder_a, folder_b],
                    },
                    [folder_a, folder_b, doc_added],
                ],
                [
                    {
                        item: doc_to_add,
                        folder_content: [folder_a, folder_b, doc_a, doc_b],
                    },
                    [folder_a, folder_b, doc_a, doc_added, doc_b],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a],
                    },
                    [folder_a, doc_added_in_folder_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, folder_b],
                    },
                    [folder_a, doc_added_in_folder_a, folder_b],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, folder_b, doc_a],
                    },
                    [folder_a, doc_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, folder_b, doc_a, doc_b],
                    },
                    [folder_a, doc_added_in_folder_a, folder_b, doc_a, doc_b],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, sub_doc_a, folder_b, doc_a],
                    },
                    [folder_a, sub_doc_a, doc_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, sub_doc_b, folder_b, doc_a],
                    },
                    [folder_a, doc_added_in_folder_a, sub_doc_b, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, sub_doc_a, sub_doc_b, folder_b, doc_a],
                    },
                    [folder_a, sub_doc_a, doc_added_in_folder_a, sub_doc_b, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, sub_folder_a, folder_b, doc_a],
                    },
                    [folder_a, sub_folder_a, doc_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, sub_folder_b, folder_b, doc_a],
                    },
                    [folder_a, sub_folder_b, doc_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [folder_a, sub_folder_a, sub_folder_b, folder_b, doc_a],
                    },
                    [folder_a, sub_folder_a, sub_folder_b, doc_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: doc_to_add_in_folder_a,
                        folder_content: [
                            folder_a,
                            sub_folder_a,
                            sub_folder_b,
                            sub_doc_a,
                            sub_doc_b,
                            folder_b,
                            doc_a,
                        ],
                    },
                    [
                        folder_a,
                        sub_folder_a,
                        sub_folder_b,
                        sub_doc_a,
                        doc_added_in_folder_a,
                        sub_doc_b,
                        folder_b,
                        doc_a,
                    ],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [],
                    },
                    [folder_added],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [doc_a],
                    },
                    [folder_added, doc_a],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [doc_b],
                    },
                    [folder_added, doc_b],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [doc_a, doc_b],
                    },
                    [folder_added, doc_a, doc_b],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [folder_a],
                    },
                    [folder_a, folder_added],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [folder_b],
                    },
                    [folder_added, folder_b],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [folder_a, folder_b],
                    },
                    [folder_a, folder_added, folder_b],
                ],
                [
                    {
                        item: folder_to_add,
                        folder_content: [folder_a, folder_b, doc_a, doc_b],
                    },
                    [folder_a, folder_added, folder_b, doc_a, doc_b],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a],
                    },
                    [folder_a, folder_added_in_folder_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, folder_b],
                    },
                    [folder_a, folder_added_in_folder_a, folder_b],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, folder_b, doc_a],
                    },
                    [folder_a, folder_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, folder_b, doc_a, doc_b],
                    },
                    [folder_a, folder_added_in_folder_a, folder_b, doc_a, doc_b],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, sub_doc_a, folder_b, doc_a],
                    },
                    [folder_a, folder_added_in_folder_a, sub_doc_a, folder_b, doc_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, sub_doc_b, folder_b, doc_a],
                    },
                    [folder_a, folder_added_in_folder_a, sub_doc_b, folder_b, doc_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, sub_doc_a, sub_doc_b, folder_b, doc_a],
                    },
                    [folder_a, folder_added_in_folder_a, sub_doc_a, sub_doc_b, folder_b, doc_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, sub_folder_a, folder_b, doc_a],
                    },
                    [folder_a, sub_folder_a, folder_added_in_folder_a, folder_b, doc_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, sub_folder_b, folder_b, doc_a],
                    },
                    [folder_a, folder_added_in_folder_a, sub_folder_b, folder_b, doc_a],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [folder_a, sub_folder_a, sub_folder_b, folder_b, doc_a],
                    },
                    [
                        folder_a,
                        sub_folder_a,
                        folder_added_in_folder_a,
                        sub_folder_b,
                        folder_b,
                        doc_a,
                    ],
                ],
                [
                    {
                        item: folder_to_add_in_folder_a,
                        folder_content: [
                            folder_a,
                            sub_folder_a,
                            sub_folder_b,
                            sub_doc_a,
                            sub_doc_b,
                            folder_b,
                            doc_a,
                        ],
                    },
                    [
                        folder_a,
                        sub_folder_a,
                        folder_added_in_folder_a,
                        sub_folder_b,
                        sub_doc_a,
                        sub_doc_b,
                        folder_b,
                        doc_a,
                    ],
                ],
            ]);
            map.forEach((expected_content, { item, folder_content }) => {
                const state = new StateBuilder().withFolderContent(folder_content).build();
                mutations.addJustCreatedItemToFolderContent(state, item);
                expect(state.folder_content).toEqual(expected_content);
            });
        });
    });

    describe("replaceUploadingFileWithActualFile", () => {
        it("should replace the fake item by the actual item in the folder content", () => {
            const fake_item = new FakeItemBuilder(46)
                .withTitle("toto.txt")
                .withParentId(42)
                .withType("file")
                .withFileType("plain/text")
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
                    fake_item,
                    new ItemBuilder(43)
                        .withParentId(42)
                        .withLevel(3)
                        .withType("file")
                        .withTitle("tutu.txt")
                        .build(),
                ])
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
