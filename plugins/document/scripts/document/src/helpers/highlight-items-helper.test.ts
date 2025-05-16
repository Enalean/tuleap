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

import { highlightItem } from "./highlight-items-helper";
import { TYPE_FILE, TYPE_FOLDER } from "../constants";
import type { Folder, ItemFile } from "../type";

describe("highlight-items-helper", () => {
    describe("Highlight preview pane", () => {
        it("When the hovered item is the preview of a file, then it should highlight it", () => {
            const item: ItemFile = {
                user_can_write: true,
                type: TYPE_FILE,
            } as ItemFile;

            const closest_row = document.createElement("div");
            closest_row.classList.add("document-quick-look-pane");

            highlightItem(item, closest_row);

            expect(closest_row.classList.contains("quick-look-pane-highlighted")).toBe(true);
            expect(closest_row.classList.contains("document-file-highlighted")).toBe(true);
        });

        it("When the hovered item is the preview of a folder, then it should highlight it", () => {
            const item: Folder = {
                user_can_write: true,
                type: TYPE_FOLDER,
            } as Folder;

            const closest_row = document.createElement("div");
            closest_row.classList.add("document-quick-look-pane");

            highlightItem(item, closest_row);

            expect(closest_row.classList.contains("quick-look-pane-highlighted")).toBe(true);
            expect(closest_row.classList.contains("document-folder-highlighted")).toBe(true);
        });
    });

    describe("Highlight a row in the tree view", () => {
        it("should highlight the file in the tree view", () => {
            const item: ItemFile = {
                user_can_write: true,
                type: TYPE_FILE,
            } as ItemFile;

            const closest_row = document.createElement("div");
            closest_row.classList.add("document-tree-item");

            highlightItem(item, closest_row);

            expect(closest_row.classList.contains("document-tree-item-highlighted")).toBe(true);
            expect(closest_row.classList.contains("document-file-highlighted")).toBe(true);
        });

        it("should highlight the folder in the tree view", () => {
            const item: Folder = {
                user_can_write: true,
                type: TYPE_FOLDER,
            } as Folder;

            const closest_row = document.createElement("div");
            closest_row.classList.add("document-tree-item");

            highlightItem(item, closest_row);

            expect(closest_row.classList.contains("document-tree-item-highlighted")).toBe(true);
            expect(closest_row.classList.contains("document-folder-highlighted")).toBe(true);
        });
    });

    describe("when user does not have the permission to write the item", () => {
        it("should apply the 'forbiden' class on tree view items", () => {
            const item: Folder = {
                user_can_write: false,
                type: TYPE_FOLDER,
            } as Folder;

            const closest_row = document.createElement("div");
            closest_row.classList.add("document-tree-item");

            highlightItem(item, closest_row);

            expect(closest_row.classList.contains("document-tree-item-highlighted")).toBe(true);
            expect(
                closest_row.classList.contains("document-tree-item-hightlighted-forbidden"),
            ).toBe(true);
        });

        it("should apply the 'forbidden' class on quick look pane", () => {
            const item: Folder = {
                user_can_write: false,
                type: TYPE_FOLDER,
            } as Folder;

            const closest_row = document.createElement("div");
            closest_row.classList.add("document-quick-look-pane");

            highlightItem(item, closest_row);

            expect(closest_row.classList.contains("quick-look-pane-highlighted-forbidden")).toBe(
                true,
            );
        });
    });
});
