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

import { tlp } from "tlp-mocks";
import { loadRootDocumentId } from "./actions.js";
import {
    restore as restoreRestQuerier,
    rewire$getProject,
    rewire$getFolderContent
} from "../api/rest-querier.js";

describe("Store actions", () => {
    afterEach(() => {
        restoreRestQuerier();
        tlp.recursiveGet.and.stub();
    });

    describe("loadRootDocumentId()", () => {
        let context, getFolderContent, getProject;

        beforeEach(() => {
            const project_id = 101;
            context = {
                commit: jasmine.createSpy("commit"),
                state: {
                    project_id
                }
            };

            getFolderContent = jasmine.createSpy("getFolderContent");
            rewire$getFolderContent(getFolderContent);

            getProject = jasmine.createSpy("getProject");
            rewire$getProject(getProject);
        });

        it("load document root and then load its own content", async () => {
            const project = {
                additional_informations: {
                    docman: {
                        root_item: {
                            item_id: 3,
                            name: "Project Documentation",
                            owner: {
                                id: 101,
                                display_name: "user (login)"
                            },
                            last_update_date: "2018-08-21T17:01:49+02:00"
                        }
                    }
                }
            };

            getProject.and.returnValue(project);

            const folder_content = [
                {
                    item_id: 1,
                    name: "folder",
                    owner: {
                        id: 101
                    },
                    last_update_date: "2018-10-03T11:16:11+02:00"
                },
                {
                    item_id: 2,
                    name: "item",
                    owner: {
                        id: 101
                    },
                    last_update_date: "2018-08-07T16:42:49+02:00"
                }
            ];

            getFolderContent.and.returnValue(folder_content);

            await loadRootDocumentId(context);

            expect(context.commit).toHaveBeenCalledWith("switchLoadingFolder", true);
            expect(context.commit).toHaveBeenCalledWith("saveDocumentRootId", 3);
            expect(context.commit).toHaveBeenCalledWith("saveFolderContent", folder_content);
            expect(context.commit).toHaveBeenCalledWith("switchLoadingFolder", false);
        });

        it("When the root project can not be loaded, an error will be raised", async () => {
            getProject.and.returnValue(
                Promise.reject({
                    response: {
                        json() {
                            return Promise.resolve({
                                error: {
                                    status: 403,
                                    message: "Forbidden"
                                }
                            });
                        }
                    }
                })
            );

            await loadRootDocumentId(context);

            expect(context.commit).toHaveBeenCalledWith("switchLoadingFolder", true);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "Forbidden");
            expect(context.commit).toHaveBeenCalledWith("switchLoadingFolder", false);
        });

        it("When the folder content can not be loaded, an error will be raised", async () => {
            const project = {
                additional_informations: {
                    docman: {
                        root_item: {
                            item_id: 3,
                            name: "Project Documentation",
                            owner: {
                                id: 101,
                                display_name: "user (login)"
                            },
                            last_update_date: "2018-08-21T17:01:49+02:00"
                        }
                    }
                }
            };

            getProject.and.returnValue(project);

            getFolderContent.and.returnValue(
                Promise.reject({
                    response: {
                        json() {
                            return Promise.resolve({
                                error: {
                                    status: 403,
                                    message: "No you cannot"
                                }
                            });
                        }
                    }
                })
            );

            await loadRootDocumentId(context);

            expect(context.commit).toHaveBeenCalledWith("switchLoadingFolder", true);
            expect(context.commit).toHaveBeenCalledWith("saveDocumentRootId", 3);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "No you cannot");
            expect(context.commit).toHaveBeenCalledWith("switchLoadingFolder", false);
        });
    });
});
