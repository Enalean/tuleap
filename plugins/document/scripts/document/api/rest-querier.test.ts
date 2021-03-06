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
    addNewEmpty,
    addNewFolder,
    addNewLink,
    addNewWiki,
    addNewFile,
    createNewVersion,
    getDocumentManagerServiceInformation,
    getFolderContent,
    getItem,
    getParents,
    getProjectUserGroups,
    postEmbeddedFile,
    postLinkVersion,
    postWiki,
} from "./rest-querier";
import type { ProjectService } from "./rest-querier";

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp from "tlp";
import type {
    Empty,
    FileProperties,
    Folder,
    Item,
    ItemFile,
    Wiki,
    User,
    Link,
    Embedded,
} from "../type";

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
            } as Item;
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
            } as ProjectService;
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
                } as Folder,
                {
                    id: 2,
                    title: "folder",
                    owner: {
                        id: 101,
                        display_name: "docmanusername (docmanuserlogin)",
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00",
                } as Folder,
            ];
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");
            tlpRecursiveGet.mockResolvedValue(items);

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
                    id: 1,
                    title: "folder A",
                    owner: {
                        id: 101,
                        display_name: "username (userlogin)",
                    } as User,
                    last_update_date: "2018-10-03T11:16:11+02:00",
                } as Folder,
                {
                    id: 2,
                    title: "folder B",
                    owner: {
                        id: 101,
                        display_name: "docmanusername (docmanuserlogin)",
                    } as User,
                    last_update_date: "2018-10-03T11:16:11+02:00",
                } as Folder,
            ];
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");
            tlpRecursiveGet.mockResolvedValue(parents);

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

    describe("createNewVersion()", () => {
        it("Given data are valid, then a new version of item will be created", async () => {
            const item = {
                file_properties: {
                    file_size: 123,
                } as FileProperties,
            } as ItemFile;
            const dropped_file = {
                file_properties: {
                    file_size: 124,
                } as FileProperties,
            } as ItemFile;
            const tlpPost = jest.spyOn(tlp, "post");

            mockFetchSuccess(tlpPost, { return_json: JSON.stringify(JSON.stringify({ id: 10 })) });

            await createNewVersion(item, "my document title", "", dropped_file, false, null);
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
                } as Folder,
                2
            );

            expect(tlpPost).toHaveBeenCalledWith("/api/docman_folders/2/folders", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: item,
            });
        });
    });
    describe("addNewFile()", () => {
        it("Create a new file", async () => {
            const item = JSON.stringify({
                title: "my new file",
                description: "",
                type: "file",
            } as ItemFile);
            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewFile(
                {
                    title: "my new file",
                    description: "",
                    type: "file",
                } as ItemFile,
                2
            );

            expect(tlpPost).toHaveBeenCalledWith("/api/docman_folders/2/files", {
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
                } as Empty,
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
                } as Wiki,
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
                } as Link,
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
            const item = {
                title: "Hello",
                description: "Howdy!",
                type: "embedded",
            } as Embedded;

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
            const item = {
                title: "Kinky wiki",
                description: "Not for children",
                type: "wiki",
            } as Wiki;

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
            const item = {
                title: "A link to the past",
                description: "Time travel machine is here",
                type: "link",
            } as Link;

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
