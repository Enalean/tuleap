/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import * as mutations from "./mutations-upload";
import type { FakeItem, FileProperties, Folder, ItemFile, State } from "../type";

describe("Store mutations", () => {
    describe("addFileInUploadsList", () => {
        it("should add the file at the beggining of the upload list", () => {
            const file = {
                id: 5,
                title: "tyty.txt",
            } as FakeItem;

            const state = {
                files_uploads_list: [
                    { id: 4, title: "tete.txt" } as ItemFile,
                    { id: 3, title: "tata.txt" } as ItemFile,
                    { id: 2, title: "titi.txt" } as ItemFile,
                    { id: 1, title: "tutu.txt" } as ItemFile,
                ],
            } as State;

            mutations.addFileInUploadsList(state, file);

            expect(state.files_uploads_list).toStrictEqual([
                file,
                { id: 4, title: "tete.txt" },
                { id: 3, title: "tata.txt" },
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt" },
            ]);
        });
    });

    describe("removeFileFromUploadsList", () => {
        it("should remove file from the upload list", () => {
            const file = {
                id: 5,
                title: "tyty.txt",
            } as FakeItem;

            const state = {
                files_uploads_list: [
                    file,
                    { id: 4, title: "tete.txt" } as ItemFile,
                    { id: 3, title: "tata.txt" } as ItemFile,
                    { id: 2, title: "titi.txt" } as ItemFile,
                    { id: 1, title: "tutu.txt" } as ItemFile,
                ],
            } as State;

            mutations.removeFileFromUploadsList(state, file);

            expect(state.files_uploads_list).toStrictEqual([
                { id: 4, title: "tete.txt" },
                { id: 3, title: "tata.txt" },
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt" },
            ]);
        });

        it("should toggle parent has uploading file if all items are canceled", () => {
            const file = {
                id: 5,
                title: "tyty.txt",
                parent_id: 1,
            } as FakeItem;

            const state = {
                files_uploads_list: [{ id: 5, title: "tyty.txt", parent_id: 1 }],
                folder_content: [
                    {
                        id: 1,
                        title: "My folder",
                        progress: 75,
                        is_uploading_in_collapsed_folder: true,
                    },
                ],
            } as State;

            mutations.removeFileFromUploadsList(state, file);

            expect(state.folder_content).toStrictEqual([
                { id: 1, title: "My folder", progress: 75, is_uploading_in_collapsed_folder: true },
            ]);
        });
    });

    describe("initializeFolderProperties", () => {
        it("should not do anything if folder is not found", () => {
            const folder = {
                id: 5,
                title: "toto.txt",
            } as Folder;

            const state = {
                folder_content: [
                    { id: 2, title: "titi.txt" },
                    { id: 1, title: "tutu.txt" },
                ],
            } as State;

            mutations.initializeFolderProperties(state, folder);

            expect(state.folder_content).toStrictEqual([
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt" },
            ]);
        });
    });

    describe("toggleCollapsedFolderHasUploadingContent", () => {
        it("should not do anything if folder is not found", () => {
            const folder = {
                id: 5,
                title: "toto.txt",
            } as Folder;

            const state = {
                folder_content: [
                    { id: 2, title: "titi.txt" },
                    { id: 1, title: "tutu.txt" },
                ],
            } as State;

            mutations.toggleCollapsedFolderHasUploadingContent(state, {
                collapsed_folder: folder,
                toggle: true,
            });

            expect(state.folder_content).toStrictEqual([
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt" },
            ]);
        });

        it("should toggle upload is done in a collapsed folder", () => {
            const folder = {
                id: 1,
                title: "tutu.txt",
            } as Folder;

            const state = {
                folder_content: [
                    { id: 2, title: "titi.txt" },
                    { id: 1, title: "tutu.txt" },
                ],
            } as State;

            mutations.toggleCollapsedFolderHasUploadingContent(state, {
                collapsed_folder: folder,
                toggle: true,
            });

            expect(state.folder_content).toStrictEqual([
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt", is_uploading_in_collapsed_folder: true, progress: 0 },
            ]);
        });
    });

    describe("updateFolderProgressbar", () => {
        it("should not do anything if folder is not found", () => {
            const folder = {
                id: 5,
                title: "toto.txt",
            } as Folder;

            const state = {
                folder_content: [
                    { id: 2, title: "titi.txt" },
                    { id: 1, title: "tutu.txt" },
                ],
            } as State;

            mutations.updateFolderProgressbar(state, folder);

            expect(state.folder_content).toStrictEqual([
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt" },
            ]);
        });

        it("should store the progress of folder by computing the progress of its children", () => {
            const folder = {
                id: 1,
                title: "tutu.txt",
            } as Folder;

            const state = {
                folder_content: [{ id: 1, title: "tutu.txt", progress: null }],
                files_uploads_list: [
                    { id: 2, title: "tutu.txt", progress: 25, parent_id: 1 },
                    { id: 3, title: "tutu.txt", progress: 50, parent_id: 1 },
                    { id: 4, title: "tutu.txt", progress: 75, parent_id: 1 },
                ],
            } as State;

            mutations.updateFolderProgressbar(state, folder);

            expect(state.folder_content).toStrictEqual([
                { id: 1, title: "tutu.txt", progress: 50 },
            ]);
        });
    });

    describe("resetFolderIsUploading", () => {
        it("should not do anything if folder is not found", () => {
            const folder = {
                id: 5,
                title: "toto.txt",
            } as Folder;

            const state = {
                folder_content: [
                    { id: 2, title: "titi.txt" },
                    { id: 1, title: "tutu.txt" },
                ],
            } as State;

            mutations.resetFolderIsUploading(state, folder);

            expect(state.folder_content).toStrictEqual([
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt" },
            ]);
        });

        it("resets uploading properties of folder", () => {
            const folder = {
                id: 1,
                title: "tutu.txt",
            } as Folder;

            const state = {
                folder_content: [
                    { id: 2, title: "titi.txt" },
                    {
                        id: 1,
                        title: "tutu.txt",
                        is_uploading_in_collapsed_folder: true,
                        progress: 75,
                    },
                ],
            } as State;

            mutations.resetFolderIsUploading(state, folder);

            expect(state.folder_content).toStrictEqual([
                { id: 2, title: "titi.txt" },
                { id: 1, title: "tutu.txt", is_uploading_in_collapsed_folder: false, progress: 0 },
            ]);
        });
    });

    describe("replaceFileWithNewVersion", () => {
        it("should override item properties with the uploaded ones", () => {
            const random_item = {
                id: 1,
                title: "tutu.txt",
                is_uploading_in_collapsed_folder: false,
                progress: 0,
            } as FakeItem;
            const existing_item = {
                id: 2,
                title: "titi.txt",
                file_properties: {
                    download_href: "plugins/document/2/1",
                    file_size: 123,
                    file_type: "image/jpeg",
                } as FileProperties,
                lock_info: {
                    locked_by: { id: 137, uri: "users/137", user_url: "/users/user_url" },
                    lock_date: "2019-04-01T18:17:07+04:00",
                } as LockInfo,
                has_approval_table: true,
                is_approval_table_enabled: true,
                approval_table: {
                    approval_state: "Approved",
                },
            } as ItemFile;
            const new_version = {
                id: 2,
                title: "titi.txt",
                file_properties: {
                    download_href: "plugins/document/2/2",
                    file_size: 456,
                    file_type: "image/jpeg",
                },
                lock_info: null,
                has_approval_table: true,
                is_approval_table_enabled: true,
                approval_table: {
                    approval_state: "Not yet",
                },
            } as ItemFile;

            const state = {
                folder_content: [random_item, existing_item],
            } as State;

            mutations.replaceFileWithNewVersion(state, { existing_item, new_version });

            expect(state.folder_content).toStrictEqual([random_item, new_version]);
        });
    });
});
