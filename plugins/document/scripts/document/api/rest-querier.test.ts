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

import type {
    ProjectService,
    RestEmpty,
    RestFolder,
    PostRestItemFile,
    RestLink,
    RestWiki,
} from "./rest-querier";
import {
    addNewEmpty,
    addNewFile,
    addNewFolder,
    addNewLink,
    addNewWiki,
    createNewVersion,
    getDocumentManagerServiceInformation,
    getFolderContent,
    getItem,
    getParents,
    getProjectUserGroups,
    postEmbeddedFile,
    postLinkVersion,
    postWiki,
    searchInFolder,
} from "./rest-querier";

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import type {
    Embedded,
    FileProperties,
    Folder,
    Item,
    ItemFile,
    ItemSearchResult,
    Link,
    User,
    Wiki,
} from "../type";
import { buildAdvancedSearchParams } from "../helpers/build-advanced-search-params";

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
            const tlpGet = jest.spyOn(tlp_fetch, "get");
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
            const tlpGet = jest.spyOn(tlp_fetch, "get");
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
            const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
            tlpRecursiveGet.mockResolvedValue(items);

            const result = await getFolderContent(3);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/docman_items/3/docman_items", {
                params: {
                    limit: 50,
                    offset: 0,
                },
            });
            expect(tlpRecursiveGet.mock.calls).toHaveLength(1);
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
            const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
            tlpRecursiveGet.mockResolvedValue(parents);

            const result = await getParents(3);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/docman_items/3/parents", {
                params: {
                    limit: 50,
                    offset: 0,
                },
            });
            expect(tlpRecursiveGet.mock.calls).toHaveLength(1);
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
                name: "stuff.doc",
                size: 123,
            } as File;
            const tlpPost = jest.spyOn(tlp_fetch, "post");

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
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewFolder(
                {
                    title: "my new folder",
                    description: "",
                    type: "folder",
                } as RestFolder,
                2,
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
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewFile(
                {
                    title: "my new file",
                    description: "",
                    type: "file",
                } as PostRestItemFile,
                2,
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
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewEmpty(
                {
                    title: "my empty document",
                    description: "",
                    type: "empty",
                } as RestEmpty,
                2,
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
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewWiki(
                {
                    title: "my wiki document",
                    description: "",
                    type: "wiki",
                } as RestWiki,
                2,
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
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewLink(
                {
                    title: "my link document",
                    description: "",
                    type: "link",
                    link_properties: { link_url: "http://example.test" },
                } as RestLink,
                2,
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

            mockFetchSuccess(jest.spyOn(tlp_fetch, "post"));

            await postEmbeddedFile(
                item,
                content,
                version_title,
                change_log,
                should_lock_file,
                approval_table_action,
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

            mockFetchSuccess(jest.spyOn(tlp_fetch, "post"));

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

            mockFetchSuccess(jest.spyOn(tlp_fetch, "post"));

            await postLinkVersion(
                item,
                link_url,
                version_title,
                change_log,
                should_lock_file,
                approval_table_action,
            );
        });
    });

    describe("getProjectUserGroups()", () => {
        it("Given a project ID, then the REST API will be queried with it to retrieve all user groups", async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: [] });

            const result = await getProjectUserGroups(102);

            expect(tlpGet).toHaveBeenCalledWith(
                "/api/projects/102/user_groups?query=%7B%22with_system_user_groups%22%3Atrue%7D",
            );
            expect(result).toEqual([]);
        });
    });

    describe("searchInFolder", () => {
        it("should return the results with pagination information", async () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            const items = [{ id: 1 } as ItemSearchResult, { id: 2 } as ItemSearchResult];
            mockFetchSuccess(tlpPost, {
                return_json: items,
                headers: {
                    get(name): string | null {
                        if (name === "X-PAGINATION-SIZE") {
                            return "172";
                        }

                        return null;
                    },
                },
            });

            const results = await searchInFolder(
                101,
                {
                    global_search: "Lorem ipsum",
                    id: "123",
                    type: "folder",
                    filename: "bob.jpg",
                    title: "doloret",
                    description: "sit",
                    owner: "jdoe",
                    create_date: {
                        date: "2022-01-01",
                        operator: ">",
                    },
                    update_date: {
                        date: "2022-01-31",
                        operator: "<",
                    },
                    obsolescence_date: {
                        date: "2022-01-31",
                        operator: "=",
                    },
                    status: "draft",
                    field_2: "lorem",
                    field_3: {
                        date: "2022-01-31",
                        operator: "=",
                    },
                    sort: null,
                },
                170,
            );

            expect(tlpPost).toHaveBeenCalledWith(`/api/v1/docman_search/101`, {
                headers: {
                    "content-type": "application/json",
                },
                body: JSON.stringify({
                    global_search: "Lorem ipsum",
                    properties: [
                        {
                            name: "id",
                            value: "123",
                        },
                        {
                            name: "type",
                            value: "folder",
                        },
                        {
                            name: "filename",
                            value: "bob.jpg",
                        },
                        {
                            name: "title",
                            value: "doloret",
                        },
                        {
                            name: "description",
                            value: "sit",
                        },
                        {
                            name: "owner",
                            value: "jdoe",
                        },
                        {
                            name: "create_date",
                            value_date: {
                                date: "2022-01-01",
                                operator: ">",
                            },
                        },
                        {
                            name: "update_date",
                            value_date: {
                                date: "2022-01-31",
                                operator: "<",
                            },
                        },
                        {
                            name: "obsolescence_date",
                            value_date: {
                                date: "2022-01-31",
                                operator: "=",
                            },
                        },
                        {
                            name: "status",
                            value: "draft",
                        },
                        {
                            name: "field_2",
                            value: "lorem",
                        },
                        {
                            name: "field_3",
                            value_date: {
                                date: "2022-01-31",
                                operator: "=",
                            },
                        },
                    ],
                    sort: [{ name: "update_date", order: "desc" }],
                    offset: 170,
                    limit: 50,
                }),
            });
            expect(results.items).toStrictEqual(items);
            expect(results.from).toBe(170);
            expect(results.to).toBe(171);
            expect(results.total).toBe(172);
        });

        it("should exclude type from search when no specific item type is given", async () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            const items = [{ id: 1 } as ItemSearchResult, { id: 2 } as ItemSearchResult];
            mockFetchSuccess(tlpPost, {
                return_json: items,
                headers: {
                    get(name): string | null {
                        if (name === "X-PAGINATION-SIZE") {
                            return "172";
                        }

                        return null;
                    },
                },
            });

            await searchInFolder(
                101,
                buildAdvancedSearchParams({ global_search: "Lorem ipsum" }),
                170,
            );

            expect(tlpPost).toHaveBeenCalledWith(`/api/v1/docman_search/101`, {
                headers: {
                    "content-type": "application/json",
                },
                body: JSON.stringify({
                    global_search: "Lorem ipsum",
                    sort: [{ name: "update_date", order: "desc" }],
                    offset: 170,
                    limit: 50,
                }),
            });
        });
    });
});
