/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
    getProject,
    getItem,
    getParents,
    postEmbeddedFile,
    patchUserPreferenciesForFolderInProject,
    patchWiki,
    deleteUserPreferenciesForFolderInProject,
    addUserLegacyUIPreferency,
    createNewVersion
} from "./rest-querier.js";

import { tlp, mockFetchSuccess } from "tlp-mocks";
import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants.js";

describe("rest-querier", () => {
    afterEach(() => {
        tlp.get.and.stub();
        tlp.recursiveGet.and.stub();
        tlp.patch.and.stub();
        tlp.del.and.stub();
        tlp.post.and.stub();
    });

    describe("getItem()", () => {
        it("Given an item id, then the REST API will be queried with it", async () => {
            const item = {
                id: 3,
                title: "Project Documentation",
                owner: {
                    id: 101,
                    display_name: "user (login)"
                },
                last_update_date: "2018-08-21T17:01:49+02:00"
            };
            mockFetchSuccess(tlp.get, { return_json: item });

            const result = await getItem(3);

            expect(tlp.get).toHaveBeenCalledWith("/api/docman_items/3");
            expect(result).toEqual(item);
        });
    });

    describe("getProject()", () => {
        it("Given a project_id, then the REST API will be queried with it", async () => {
            const project = {
                additional_informations: {
                    docman: {
                        root_item: {
                            id: 3,
                            title: "Project Documentation",
                            owner: {
                                id: 101,
                                display_name: "user (login)"
                            },
                            last_update_date: "2018-08-21T17:01:49+02:00"
                        }
                    }
                }
            };
            mockFetchSuccess(tlp.get, { return_json: project });

            const result = await getProject(891);

            expect(tlp.get).toHaveBeenCalledWith("/api/projects/891");
            expect(result).toEqual(project);
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
                        display_name: "username (userlogin)"
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00"
                },
                {
                    id: 2,
                    title: "folder",
                    owner: {
                        id: 101,
                        display_name: "docmanusername (docmanuserlogin)"
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00"
                }
            ];
            tlp.recursiveGet.and.returnValue(items);

            const result = await getFolderContent(3);

            expect(tlp.recursiveGet).toHaveBeenCalledWith("/api/docman_items/3/docman_items", {
                params: {
                    limit: 50,
                    offset: 0
                }
            });
            expect(tlp.recursiveGet.calls.count()).toEqual(1);
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
                        display_name: "username (userlogin)"
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00"
                },
                {
                    item_id: 2,
                    name: "folder B",
                    owner: {
                        id: 101,
                        display_name: "docmanusername (docmanuserlogin)"
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00"
                }
            ];
            tlp.recursiveGet.and.returnValue(parents);

            const result = await getParents(3);

            expect(tlp.recursiveGet).toHaveBeenCalledWith("/api/docman_items/3/parents", {
                params: {
                    limit: 50,
                    offset: 0
                }
            });
            expect(tlp.recursiveGet.calls.count()).toEqual(2);
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
                "Content-Type": "application/json"
            }
        };

        describe("patchUserPreferenciesForFolderInProject() -", () => {
            it("should set the current user's preferencies for a given folder on 'expanded'", async () => {
                await patchUserPreferenciesForFolderInProject(user_id, project_id, folder_id);

                expect(tlp.patch).toHaveBeenCalledWith("/api/users/102/preferences", {
                    ...headers,
                    body: JSON.stringify({
                        key: preference_key,
                        value: DOCMAN_FOLDER_EXPANDED_VALUE
                    })
                });
            });
        });

        describe("deleteUserPreferenciesForFolderInProject() -", () => {
            it("should delete the current user's preferencies for a given folder (e.g collapsed)", async () => {
                await deleteUserPreferenciesForFolderInProject(user_id, project_id, folder_id);

                expect(tlp.del).toHaveBeenCalledWith(
                    "/api/users/102/preferences?key=plugin_docman_hide_110_30"
                );
            });
        });

        describe("addUserLegacyUIPreferency() -", () => {
            it("should set the current user's preferencies to old UI", async () => {
                mockFetchSuccess(tlp.patch, JSON.stringify({ id: 10 }));

                await addUserLegacyUIPreferency(user_id, project_id, folder_id);
                expect(tlp.patch).toHaveBeenCalled();
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
                    filesize: 123
                }
            });
            const dropped_file = {
                filename: "file",
                filesize: 123
            };

            mockFetchSuccess(tlp.post, JSON.stringify({ id: 10 }));

            await createNewVersion(item, "my document title", "", dropped_file);
            expect(tlp.post).toHaveBeenCalled();
        });
    });

    describe("addNewFolder()", () => {
        it("Create a new folder", async () => {
            const item = JSON.stringify({
                title: "my new folder",
                description: "",
                type: "folder"
            });
            mockFetchSuccess(tlp.post, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewFolder(
                {
                    title: "my new folder",
                    description: "",
                    type: "folder"
                },
                2
            );

            expect(tlp.post).toHaveBeenCalledWith("/api/docman_folders/2/folders", {
                headers: jasmine.objectContaining({ "content-type": "application/json" }),
                body: item
            });
        });
    });

    describe("addNewEmpty()", () => {
        it("Create a new empty document", async () => {
            const item = JSON.stringify({
                title: "my empty document",
                description: "",
                type: "empty"
            });
            mockFetchSuccess(tlp.post, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewEmpty(
                {
                    title: "my empty document",
                    description: "",
                    type: "empty"
                },
                2
            );

            expect(tlp.post).toHaveBeenCalledWith("/api/docman_folders/2/empties", {
                headers: jasmine.objectContaining({ "content-type": "application/json" }),
                body: item
            });
        });
    });

    describe("addNewWiki()", () => {
        it("Create a new wiki document", async () => {
            const item = JSON.stringify({
                title: "my wiki document",
                description: "",
                type: "wiki"
            });
            mockFetchSuccess(tlp.post, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewWiki(
                {
                    title: "my wiki document",
                    description: "",
                    type: "wiki"
                },
                2
            );

            expect(tlp.post).toHaveBeenCalledWith("/api/docman_folders/2/wikis", {
                headers: jasmine.objectContaining({ "content-type": "application/json" }),
                body: item
            });
        });
    });

    describe("addNewLink()", () => {
        it("Create a new link document", async () => {
            const item = JSON.stringify({
                title: "my link document",
                description: "",
                type: "link",
                link_properties: { link_url: "http://example.test" }
            });
            mockFetchSuccess(tlp.post, { return_json: { id: 66, uri: "path/to/66" } });

            await addNewLink(
                {
                    title: "my link document",
                    description: "",
                    type: "link",
                    link_properties: { link_url: "http://example.test" }
                },
                2
            );

            expect(tlp.post).toHaveBeenCalledWith("/api/docman_folders/2/links", {
                headers: jasmine.objectContaining({ "content-type": "application/json" }),
                body: item
            });
        });
    });

    describe("postEmbeddedFile()", () => {
        it("", async () => {
            const item = JSON.stringify({
                title: "Hello",
                description: "Howdy!",
                type: "embedded"
            });

            const content = "<h1>Hello world!</h1>";
            const version_title = "Hi!";
            const change_log = "update the message";
            const should_lock_file = true;
            const approval_table_action = null;

            mockFetchSuccess(tlp.patch);

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

    describe("patchWiki()", () => {
        it("", async () => {
            const item = JSON.stringify({
                title: "Kinky wiki",
                description: "Not for children",
                type: "wiki"
            });

            const page_name = "nsfw";
            const version_title = "a title";
            const change_log = "change title to nsfw";
            const should_lock_file = true;

            mockFetchSuccess(tlp.patch);

            await patchWiki(item, page_name, version_title, change_log, should_lock_file);
        });
    });

    describe("patchLink()", () => {
        it("", async () => {
            const item = JSON.stringify({
                title: "A link to the past",
                description: "Time travel machine is here",
                type: "link"
            });

            const link_url = "https://archive.org/web/web.php";
            const version_title = "Marty, get in the DeLorean!";
            const change_log = "Let's go doc!";
            const should_lock_file = true;
            const approval_table_action = null;

            mockFetchSuccess(tlp.patch);

            await patchWiki(
                item,
                link_url,
                version_title,
                change_log,
                should_lock_file,
                approval_table_action
            );
        });
    });
});
