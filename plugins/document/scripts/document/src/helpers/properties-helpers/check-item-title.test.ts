/**
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { TYPE_FILE, TYPE_FOLDER } from "../../constants";
import {
    doesDocumentAlreadyExistsAtUpdate,
    doesDocumentNameAlreadyExist,
    doesFolderNameAlreadyExist,
    doesFolderAlreadyExistsAtUpdate,
} from "./check-item-title";
import type { Folder, Item, ItemFile } from "../../type";

describe("doesFolderNameAlreadyExist", () => {
    it("Title already exists when folder name already exists", () => {
        const item_title = "my existing folder";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: item_title,
                parent_id: 3,
                type: TYPE_FOLDER,
            } as Folder,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        expect(doesFolderNameAlreadyExist(item_title, folder_content, parent_folder)).toBe(true);
    });

    it("Title does not already exist when no other folder have the same name", () => {
        const item_title = "my new folder";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: "other folder name",
                parent_id: 3,
                type: TYPE_FOLDER,
            } as Folder,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        expect(doesFolderNameAlreadyExist(item_title, folder_content, parent_folder)).toBe(false);
    });
});

describe("doesDocumentNameAlreadyExist", () => {
    it("Title already exists when file name already exists", () => {
        const item_title = "my existing file";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: item_title,
                parent_id: 3,
                type: TYPE_FILE,
            } as ItemFile,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        expect(doesDocumentNameAlreadyExist(item_title, folder_content, parent_folder)).toBe(true);
    });

    it("Title does not already exist when no other item have the same name", () => {
        const item_title = "my new document";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: "other file name",
                parent_id: 3,
                type: TYPE_FILE,
            } as ItemFile,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        expect(doesDocumentNameAlreadyExist(item_title, folder_content, parent_folder)).toBe(false);
    });
});

describe("doesDocumentAlreadyExistsAtUpdate", () => {
    it("Title already exists when file name already exists", () => {
        const item_title = "my existing document";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: item_title,
                parent_id: 3,
                type: TYPE_FILE,
            } as ItemFile,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        const item_to_update = {
            id: 300,
        } as Item;
        expect(
            doesDocumentAlreadyExistsAtUpdate(
                item_title,
                folder_content,
                item_to_update,
                parent_folder,
            ),
        ).toBe(true);
    });

    it("Title does not already exist when file has no update on title", () => {
        const item_title = "my existing folder";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: item_title,
                parent_id: 3,
                type: TYPE_FILE,
            } as ItemFile,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        const item_to_update = {
            id: 25,
        } as Item;
        expect(
            doesDocumentAlreadyExistsAtUpdate(
                item_title,
                folder_content,
                item_to_update,
                parent_folder,
            ),
        ).toBe(false);
    });

    it("Title does not already exist when no other file have the same name", () => {
        const item_title = "my new document";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: "other file name",
                parent_id: 3,
                type: TYPE_FILE,
            } as ItemFile,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        const item_to_update = {
            id: 25,
        } as Item;
        expect(
            doesDocumentAlreadyExistsAtUpdate(
                item_title,
                folder_content,
                item_to_update,
                parent_folder,
            ),
        ).toBe(false);
    });

    it("The folder title has not the same name of an other existing folder", () => {
        const item_title = "my new folder";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: "other folder name",
                parent_id: 3,
                type: TYPE_FOLDER,
            } as Folder,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        const item_to_update = {
            id: 25,
        } as Item;
        expect(
            doesFolderAlreadyExistsAtUpdate(
                item_title,
                folder_content,
                item_to_update,
                parent_folder,
            ),
        ).toBe(false);
    });
    it("The folder title has the same name of an other existing folder", () => {
        const item_title = "my existing folder";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: item_title,
                parent_id: 3,
                type: TYPE_FOLDER,
            } as Folder,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        const item_to_update = {
            id: 300,
        } as Item;
        expect(
            doesFolderAlreadyExistsAtUpdate(
                item_title,
                folder_content,
                item_to_update,
                parent_folder,
            ),
        ).toBe(true);
    });

    it("The folder title has the same name of itself", () => {
        const item_title = "my existing folder";
        const folder_content: Array<Folder | Item> = [
            {
                id: 25,
                title: item_title,
                parent_id: 3,
                type: TYPE_FOLDER,
            } as Folder,
        ];
        const parent_folder = {
            id: 3,
        } as Folder;
        const item_to_update = {
            id: 25,
        } as Item;
        expect(
            doesFolderAlreadyExistsAtUpdate(
                item_title,
                folder_content,
                item_to_update,
                parent_folder,
            ),
        ).toBe(false);
    });
});
