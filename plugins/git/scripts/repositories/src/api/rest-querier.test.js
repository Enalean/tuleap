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

import * as tlp from "tlp";
import { mockFetchSuccess } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { getRepositoryList, getForkedRepositoryList, postRepository } from "./rest-querier.js";
import { deleteIntegrationGitlab, getGitlabRepositoryList } from "./rest-querier";

jest.mock("tlp");

describe("API querier", () => {
    describe("getRepositoryList", () => {
        it("Given a project id and a callback, then it will recursively get all project repositories and call the callback for each batch", () => {
            return new Promise((done) => {
                const repositories = [{ id: 37 }, { id: 91 }];
                const tlpRecursiveGet = jest
                    .spyOn(tlp, "recursiveGet")
                    .mockImplementation((route, config) =>
                        config.getCollectionCallback({ repositories })
                    );

                function displayCallback(result) {
                    expect(result).toEqual(repositories);
                    done();
                }
                const project_id = 27;

                getRepositoryList(project_id, "push_date", displayCallback);

                expect(tlpRecursiveGet).toHaveBeenCalledWith(
                    "/api/projects/27/git",
                    expect.objectContaining({
                        params: {
                            query: '{"scope":"project"}',
                            order_by: "push_date",
                            limit: 50,
                            offset: 0,
                        },
                    })
                );
            });
        });
    });

    describe("getForkedRepositoryList", () => {
        it("Given a project id, an owner id and a callback, then it will recursively get all forks and call the callback for each batch", () => {
            return new Promise((done) => {
                const repositories = [{ id: 88 }, { id: 57 }];
                const tlpRecursiveGet = jest
                    .spyOn(tlp, "recursiveGet")
                    .mockImplementation((route, config) =>
                        config.getCollectionCallback({ repositories })
                    );
                function displayCallback(result) {
                    expect(result).toEqual(repositories);
                    done();
                }
                const project_id = 5;
                const owner_id = "477";

                getForkedRepositoryList(project_id, owner_id, "push_date", displayCallback);

                expect(tlpRecursiveGet).toHaveBeenCalledWith(
                    "/api/projects/5/git",
                    expect.objectContaining({
                        params: {
                            query: '{"scope":"individual","owner_id":477}',
                            order_by: "push_date",
                            limit: 50,
                            offset: 0,
                        },
                    })
                );
            });
        });
    });

    describe("postRepository", () => {
        it("Given a project id and a repository name, then it will post the repository to create it", async () => {
            const project_id = 6;
            const repository_name = "martial/rifleshot";

            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost);

            await postRepository(project_id, repository_name);

            const stringified_body = JSON.stringify({
                project_id,
                name: repository_name,
            });

            expect(tlpPost).toHaveBeenCalledWith("/api/git/", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: stringified_body,
            });
        });
    });

    describe("getGitlabRepositoryList", () => {
        it("Given a project id and a callback, Then it will recursively get all Gitlab repositories and call the callback for each batch", () => {
            return new Promise((done) => {
                const repositories = [{ id: 37 }, { id: 91 }];

                jest.spyOn(tlp, "recursiveGet").mockImplementation((route, config) =>
                    config.getCollectionCallback(repositories)
                );

                function displayCallback(result) {
                    expect(result).toEqual(repositories);
                    done();
                }

                const project_id = 27;

                getGitlabRepositoryList(project_id, "push_date", displayCallback);
            });
        });
    });

    describe("deleteIntegrationGitlab", () => {
        it("Given project id and repository id, Then api is queried to delete", async () => {
            const project_id = 101;
            const repository_id = 1;

            const tlpDelete = jest.spyOn(tlp, "del");
            mockFetchSuccess(tlpDelete);

            await deleteIntegrationGitlab({ project_id, repository_id });

            expect(tlpDelete).toHaveBeenCalledWith(
                "/api/v1/gitlab_repositories/" + repository_id + "?project_id=" + project_id
            );
        });
    });
});
