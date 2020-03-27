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

import {
    addNewLink,
    addNewFolder,
    addNewEmpty,
    addNewWiki,
    getFolderContent,
    getDocumentManagerServiceInformation,
    getItem,
    getParents,
    postEmbeddedFile,
    patchUserPreferenciesForFolderInProject,
    postWiki,
    deleteUserPreferenciesForFolderInProject,
    addUserLegacyUIPreferency,
    createNewVersion,
    postLinkVersion,
    moveFile,
    moveFolder,
    moveEmpty,
    moveWiki,
    moveEmbedded,
    moveLink,
    copyFile,
    copyFolder,
    copyEmpty,
    copyWiki,
    copyEmbedded,
    copyLink,
    getProjectUserGroups,
    putEmbeddedFilePermissions,
    putFilePermissions,
    putLinkPermissions,
    putWikiPermissions,
    putEmptyDocumentPermissions,
    putFolderPermissions,
} from "./rest-querier.js";

import { mockFetchSuccess } from "../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper.js";
import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants.js";
import * as tlp from "tlp";

jest.mock("tlp");

describe("rest-querier", () => {
    describe("getItem()", () => {
        it("Given an item id, then the REST API will be queried with it", async () => {
            const item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            };
            const tlpGet = jest.spyOn(tlp, "get");
            mockFetchSuccess(tlpGet, { return_json: item });

            const result = await getItem(3);

            expect(tlpGet).toHaveBeenCalledWith("/api/docman_items/3");
            expect(result).toEqual(item);
        });
    });

    describe("getDocumentManagerServiceInformation()", () => {
        it("Given a project_id, then the REST API will be queried with it", async () => {
            const service = {
                root_item: {
                    id: 3,
                    title: "Project Documentation",
                    owner: {
                        id: 101,
                        display_name: "user (login)",
                    },
                    last_update_date: "2018-08-21T17:01:49+02:00",
                },
            };
            const tlpGet = jest.spyOn(tlp, "get");
            mockFetchSuccess(tlpGet, { return_json: service });

            const result = await getDocumentManagerServiceInformation(891);

            expect(tlpGet).toHaveBeenCalledWith("/api/projects/891/docman_service");
            expect(result).toEqual(service);
        });
    });

    describe("getFolderContent() -", () => {
        it("the REST API will be queried and items under folder will be returned", async () => {
            const items = [
                {
                    id: 1,
                    title: "folder",
                    owner: {
                        id: 101,
                        display_name: "username (userlogin)",
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00",
                },
                {
                    id: 2,
                    title: "folder",
                    owner: {
                        id: 101,
                        display_name: "docmanusername (docmanuserlogin)",
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00",
                },
            ];
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");
            tlpRecursiveGet.mockReturnValue(items);

            const result = await getFolderContent(3);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/docman_items/3/docman_items", {
                params: {
                    limit: 50,
                    offset: 0,
                },
            });
            expect(tlpRecursiveGet.mock.calls.length).toEqual(1);
            expect(result).toEqual(items);
        });
    });

    describe("getParents() -", () => {
        it("the REST API will be queried and parents of will be returned", async () => {
            const parents = [
                {
                    item_id: 1,
                    name: "folder A",
                    owner: {
                        id: 101,
                        display_name: "username (userlogin)",
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00",
                },
                {
                    item_id: 2,
                    name: "folder B",
                    owner: {
                        id: 101,
                        display_name: "docmanusername (docmanuserlogin)",
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00",
                },
            ];
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");
            tlpRecursiveGet.mockReturnValue(parents);

            const result = await getParents(3);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/docman_items/3/parents", {
                params: {
                    limit: 50,
                    offset: 0,
                },
            });
            expect(tlpRecursiveGet.mock.calls.length).toEqual(2);
            expect(result).toEqual(parents);
        });
    });

    describe("User preferences", () => {
        const user_id = 102;
        const project_id = 110;
        const folder_id = 30;
        const preference_key = "plugin_docman_hide_110_30";
        const headers = {
            headers: {
                "Content-Type": "application/json",
            },
        };

        describe("patchUserPreferenciesForFolderInProject() -", () => {
            it("should set the current user's preferencies for a given folder on 'expanded'", async () => {
                const tlpPatch = jest.spyOn(tlp, "patch");
                await patchUserPreferenciesForFolderInProject(user_id, project_id, folder_id);

                expect(tlpPatch).toHaveBeenCalledWith("/api/users/102/preferences", {
                    ...headers,
                    body: JSON.stringify({
                        key: preference_key,
                        value: DOCMAN_FOLDER_EXPANDED_VALUE,
                    }),
                });
            });
        });

        describe("deleteUserPreferenciesForFolderInProject() -", () => {
            it("should delete the current user's preferencies for a given folder (e.g collapsed)", async () => {
                const tlpDel = jest.spyOn(tlp, "del");
                await deleteUserPreferenciesForFolderInProject(user_id, project_id, folder_id);

                expect(tlpDel).toHaveBeenCalledWith(
                    "/api/users/102/preferences?key=plugin_docman_hide_110_30"
                );
            });
        });

        describe("addUserLegacyUIPreferency() -", () => {
            it("should set the current user's preferencies to old UI", async () => {
                const tlpPatch = jest.spyOn(tlp, "patch");
                mockFetchSuccess(tlpPatch, JSON.stringify({ id: 10 }));

                await addUserLegacyUIPreferency(user_id, project_id, folder_id);
                expect(tlpPatch).toHaveBeenCalled();
            });
        });
    });

    describe("createNewVersion()", () => {
        it("Given data are valid, then a new version of item will be created", async () => {
            const item = JSON.stringify({
                version_title: "my document title",
                changelog: "",
                file_properties: {
                    filename: "file",
                    filesize: 123,
                },
            });
            const dropped_file = {
                filename: "file",
                filesize: 123,
            };
            const tlpPost = jest.spyOn(tlp, "post");

            mockFetchSuccess(tlpPost, JSON.stringify({ id: 10 }));

            await createNewVersion(item, "my document title", "", dropped_file);
            expect(tlpPost).toHaveBeenCalled();
        });
    });

    describe("addNewFolder()", () => {
        it("Create a new folder", async () => {
            const item = JSON.stringify({
                title: "my new folder",
                description: "",
                type: "folder",
            });
            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewFolder(
                {
                    title: "my new folder",
                    description: "",
                    type: "folder",
                },
                2
            );

            expect(tlpPost).toHaveBeenCalledWith("/api/docman_folders/2/folders", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: item,
            });
        });
    });

    describe("addNewEmpty()", () => {
        it("Create a new empty document", async () => {
            const item = JSON.stringify({
                title: "my empty document",
                description: "",
                type: "empty",
            });
            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewEmpty(
                {
                    title: "my empty document",
                    description: "",
                    type: "empty",
                },
                2
            );

            expect(tlpPost).toHaveBeenCalledWith("/api/docman_folders/2/empties", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: item,
            });
        });
    });

    describe("addNewWiki()", () => {
        it("Create a new wiki document", async () => {
            const item = JSON.stringify({
                title: "my wiki document",
                description: "",
                type: "wiki",
            });
            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewWiki(
                {
                    title: "my wiki document",
                    description: "",
                    type: "wiki",
                },
                2
            );

            expect(tlpPost).toHaveBeenCalledWith("/api/docman_folders/2/wikis", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: item,
            });
        });
    });

    describe("addNewLink()", () => {
        it("Create a new link document", async () => {
            const item = JSON.stringify({
                title: "my link document",
                description: "",
                type: "link",
                link_properties: { link_url: "http://example.test" },
            });
            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewLink(
                {
                    title: "my link document",
                    description: "",
                    type: "link",
                    link_properties: { link_url: "http://example.test" },
                },
                2
            );

            expect(tlpPost).toHaveBeenCalledWith("/api/docman_folders/2/links", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: item,
            });
        });
    });

    describe("postEmbeddedFile()", () => {
        it("Creates an embedded file", async () => {
            const item = JSON.stringify({
                title: "Hello",
                description: "Howdy!",
                type: "embedded",
            });

            const content = "<h1>Hello world!</h1>";
            const version_title = "Hi!";
            const change_log = "update the message";
            const should_lock_file = true;
            const approval_table_action = null;

            mockFetchSuccess(jest.spyOn(tlp, "post"));

            await postEmbeddedFile(
                item,
                content,
                version_title,
                change_log,
                should_lock_file,
                approval_table_action
            );
        });
    });

    describe("postWiki()", () => {
        it("Creates a wiki document", async () => {
            const item = JSON.stringify({
                title: "Kinky wiki",
                description: "Not for children",
                type: "wiki",
            });

            const page_name = "nsfw";
            const version_title = "a title";
            const change_log = "change title to nsfw";
            const should_lock_file = true;

            mockFetchSuccess(jest.spyOn(tlp, "post"));

            await postWiki(item, page_name, version_title, change_log, should_lock_file);
        });
    });

    describe("postLinkVersion()", () => {
        it("Creates a link version", async () => {
            const item = JSON.stringify({
                title: "A link to the past",
                description: "Time travel machine is here",
                type: "link",
            });

            const link_url = "https://archive.org/web/web.php";
            const version_title = "Marty, get in the DeLorean!";
            const change_log = "Let's go doc!";
            const should_lock_file = true;
            const approval_table_action = null;

            mockFetchSuccess(jest.spyOn(tlp, "post"));

            await postLinkVersion(
                item,
                link_url,
                version_title,
                change_log,
                should_lock_file,
                approval_table_action
            );
        });
    });

    describe("Move item", () => {
        const moved_item_id = 147;
        const destination_folder_id = 852;
        let tlpPatch;

        beforeEach(() => {
            tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);
        });

        it("Move a file", async () => {
            await moveFile(moved_item_id, destination_folder_id);

            expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_files/${moved_item_id}`, {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
            });
        });

        it("Move an empty document", async () => {
            await moveEmpty(moved_item_id, destination_folder_id);

            expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_empty_documents/${moved_item_id}`, {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
            });
        });

        it("Move an embedded document", async () => {
            await moveEmbedded(moved_item_id, destination_folder_id);

            expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_embedded_files/${moved_item_id}`, {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
            });
        });

        it("Move a wiki document", async () => {
            await moveWiki(moved_item_id, destination_folder_id);

            expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_wikis/${moved_item_id}`, {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
            });
        });

        it("Move a link document", async () => {
            await moveLink(moved_item_id, destination_folder_id);

            expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_links/${moved_item_id}`, {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
            });
        });

        it("Move a folder", async () => {
            await moveFolder(moved_item_id, destination_folder_id);

            expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_folders/${moved_item_id}`, {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
            });
        });
    });

    describe("Copy item", () => {
        const copied_item_id = 147;
        const destination_folder_id = 852;
        let tlpPost;

        beforeEach(() => {
            tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, JSON.stringify({ id: 963, uri: "path/to/963" }));
        });

        it("Create a copy of a file", async () => {
            await copyFile(copied_item_id, destination_folder_id);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/docman_folders/${destination_folder_id}/files`,
                {
                    headers: expect.objectContaining({ "content-type": "application/json" }),
                    body: JSON.stringify({ copy: { item_id: copied_item_id } }),
                }
            );
        });

        it("Create a copy of an empty document", async () => {
            await copyEmpty(copied_item_id, destination_folder_id);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/docman_folders/${destination_folder_id}/empties`,
                {
                    headers: expect.objectContaining({ "content-type": "application/json" }),
                    body: JSON.stringify({ copy: { item_id: copied_item_id } }),
                }
            );
        });

        it("Create a copy of an embedded document", async () => {
            await copyEmbedded(copied_item_id, destination_folder_id);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/docman_folders/${destination_folder_id}/embedded_files`,
                {
                    headers: expect.objectContaining({ "content-type": "application/json" }),
                    body: JSON.stringify({ copy: { item_id: copied_item_id } }),
                }
            );
        });

        it("Create a copy of a wiki document", async () => {
            await copyWiki(copied_item_id, destination_folder_id);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/docman_folders/${destination_folder_id}/wikis`,
                {
                    headers: expect.objectContaining({ "content-type": "application/json" }),
                    body: JSON.stringify({ copy: { item_id: copied_item_id } }),
                }
            );
        });

        it("Create a copy of a link document", async () => {
            await copyLink(copied_item_id, destination_folder_id);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/docman_folders/${destination_folder_id}/links`,
                {
                    headers: expect.objectContaining({ "content-type": "application/json" }),
                    body: JSON.stringify({ copy: { item_id: copied_item_id } }),
                }
            );
        });

        it("Create a copy of a folder", async () => {
            await copyFolder(copied_item_id, destination_folder_id);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/docman_folders/${destination_folder_id}/folders`,
                {
                    headers: expect.objectContaining({ "content-type": "application/json" }),
                    body: JSON.stringify({ copy: { item_id: copied_item_id } }),
                }
            );
        });
    });

    describe("Update item permissions", () => {
        const item_id = 123;
        const permissions = {
            can_read: [],
            can_write: [],
            can_manage: [],
        };
        let tlpPut;

        beforeEach(() => {
            tlpPut = jest.spyOn(tlp, "put");
            mockFetchSuccess(tlpPut);
        });

        it("Update permissions of a file", async () => {
            await putFilePermissions(item_id, permissions);

            expect(tlpPut).toHaveBeenCalledWith(`/api/docman_files/${item_id}/permissions`, {
                headers: expect.objectContaining({ "Content-Type": "application/json" }),
                body: JSON.stringify(permissions),
            });
        });

        it("Update permissions of an embedded file", async () => {
            await putEmbeddedFilePermissions(item_id, permissions);

            expect(tlpPut).toHaveBeenCalledWith(
                `/api/docman_embedded_files/${item_id}/permissions`,
                {
                    headers: expect.objectContaining({ "Content-Type": "application/json" }),
                    body: JSON.stringify(permissions),
                }
            );
        });

        it("Update permissions of a link", async () => {
            await putLinkPermissions(item_id, permissions);

            expect(tlpPut).toHaveBeenCalledWith(`/api/docman_links/${item_id}/permissions`, {
                headers: expect.objectContaining({ "Content-Type": "application/json" }),
                body: JSON.stringify(permissions),
            });
        });

        it("Update permissions of a wiki document", async () => {
            await putWikiPermissions(item_id, permissions);

            expect(tlp.put).toHaveBeenCalledWith(`/api/docman_wikis/${item_id}/permissions`, {
                headers: expect.objectContaining({ "Content-Type": "application/json" }),
                body: JSON.stringify(permissions),
            });
        });

        it("Update permissions of an empty document", async () => {
            await putEmptyDocumentPermissions(item_id, permissions);

            expect(tlpPut).toHaveBeenCalledWith(
                `/api/docman_empty_documents/${item_id}/permissions`,
                {
                    headers: expect.objectContaining({ "Content-Type": "application/json" }),
                    body: JSON.stringify(permissions),
                }
            );
        });

        it("Update permissions of folder", async () => {
            await putFolderPermissions(item_id, permissions);

            expect(tlpPut).toHaveBeenCalledWith(`/api/docman_folders/${item_id}/permissions`, {
                headers: expect.objectContaining({ "Content-Type": "application/json" }),
                body: JSON.stringify(permissions),
            });
        });
    });

    describe("getProjectUserGroups()", () => {
        it("Given a project ID, then the REST API will be queried with it to retrieve all user groups", async () => {
            const tlpGet = jest.spyOn(tlp, "get");
            mockFetchSuccess(tlpGet, { return_json: [] });

            const result = await getProjectUserGroups(102);

            expect(tlpGet).toHaveBeenCalledWith(
                "/api/projects/102/user_groups?query=%7B%22with_system_user_groups%22%3Atrue%7D"
            );
            expect(result).toEqual([]);
        });
    });
});
