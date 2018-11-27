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

import { getFolderContent, getProject, getItem, getParents } from "./rest-querier.js";
import { tlp, mockFetchSuccess } from "tlp-mocks";

describe("rest-querier", () => {
    afterEach(() => {
        tlp.get.and.stub();
        tlp.recursiveGet.and.stub();
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
});
