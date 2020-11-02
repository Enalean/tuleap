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
    mockFetchSuccess,
    mockFetchError,
} from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import {
    PROJECT_KEY,
    ERROR_TYPE_NO_GIT,
    ERROR_TYPE_UNKNOWN_ERROR,
    REPOSITORIES_SORTED_BY_LAST_UPDATE,
    REPOSITORIES_SORTED_BY_PATH,
    ANONYMOUS_USER_ID,
} from "../constants.js";
import {
    setDisplayMode,
    getAsyncRepositoryList,
    changeRepositories,
    getGitlabRepositoryList,
} from "./actions.js";
import * as repository_list_presenter from "../repository-list-presenter.js";
import * as rest_querier from "../api/rest-querier.js";
import * as gitlab_querier from "../gitlab/gitlab-api-querier";

describe("Store actions", () => {
    describe("setDisplayMode", () => {
        let context,
            setRepositoriesSortedByPathUserPreference,
            deleteRepositoriesSortedByPathUserPreference;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
            };

            setRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "setRepositoriesSortedByPathUserPreference"
            );
            deleteRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "deleteRepositoriesSortedByPathUserPreference"
            );
        });

        it("commits the new mode", async () => {
            const getUserId = jest.spyOn(repository_list_presenter, "getUserId");
            getUserId.mockReturnValue(0);

            const new_mode = REPOSITORIES_SORTED_BY_PATH;

            await setDisplayMode(context, new_mode);

            expect(context.commit).toHaveBeenCalledWith("setDisplayMode", new_mode);
        });

        it("does not save user preference if user is anonymous", async () => {
            const getUserId = jest.spyOn(repository_list_presenter, "getUserId");
            getUserId.mockReturnValue(ANONYMOUS_USER_ID);

            const new_mode = REPOSITORIES_SORTED_BY_PATH;

            await setDisplayMode(context, new_mode);

            expect(setRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
            expect(deleteRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
        });

        it("saves user preferences if by path", async () => {
            const getUserId = jest.spyOn(repository_list_presenter, "getUserId");
            getUserId.mockReturnValue(101);

            mockFetchSuccess(setRepositoriesSortedByPathUserPreference);

            const new_mode = REPOSITORIES_SORTED_BY_PATH;

            await setDisplayMode(context, new_mode);

            expect(setRepositoriesSortedByPathUserPreference).toHaveBeenCalledWith(101);
            expect(deleteRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
        });

        it("deletes user preferences if not by path", async () => {
            const getUserId = jest.spyOn(repository_list_presenter, "getUserId");
            getUserId.mockReturnValue(101);

            mockFetchSuccess(deleteRepositoriesSortedByPathUserPreference);

            const new_mode = REPOSITORIES_SORTED_BY_LAST_UPDATE;

            await setDisplayMode(context, new_mode);

            expect(deleteRepositoriesSortedByPathUserPreference).toHaveBeenCalledWith(101);
            expect(setRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
        });
    });

    describe("changeRepositories", () => {
        const current_project_id = 100;

        let getRepositoryList, getForkedRepositoryList, getProjectId;

        beforeEach(() => {
            getRepositoryList = jest.spyOn(rest_querier, "getRepositoryList");

            getForkedRepositoryList = jest.spyOn(rest_querier, "getForkedRepositoryList");

            getProjectId = jest.spyOn(repository_list_presenter, "getProjectId");
            getProjectId.mockImplementation(() => current_project_id);
        });

        it("Given that my repositories have already been loaded, then it should not try to fetch the list of repositories.", async () => {
            const context = {
                commit: jest.fn(),
                getters: {
                    areRepositoriesAlreadyLoadedForCurrentOwner: true,
                },
            };

            const new_owner_id = 101;

            await changeRepositories(context, new_owner_id);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", new_owner_id);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(getRepositoryList).not.toHaveBeenCalled();
            expect(getForkedRepositoryList).not.toHaveBeenCalled();
        });

        it("Given that my repositories have not already been loaded, When I pass the PROJECT_KEY in parameters, then it should fetch the list of repositories of the project.", async () => {
            const context = {
                commit: jest.fn(),
                getters: {
                    areRepositoriesAlreadyLoadedForCurrentOwner: false,
                    isFolderDisplayMode: false,
                },
            };

            mockFetchSuccess(getRepositoryList);

            await changeRepositories(context, PROJECT_KEY);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", PROJECT_KEY);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(getRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                "push_date",
                expect.any(Function)
            );
            expect(getForkedRepositoryList).not.toHaveBeenCalled();
        });

        it("Given that my repositories have not already been loaded, When I pass an user id in parameters, then it should fetch the list of forked repositories of the project.", async () => {
            const selected_owner_id = 120;
            const context = {
                commit: jest.fn(),
                getters: {
                    areRepositoriesAlreadyLoadedForCurrentOwner: false,
                    isFolderDisplayMode: false,
                },
                state: {
                    selected_owner_id,
                },
            };

            mockFetchSuccess(getForkedRepositoryList);

            const owner_id = 101;

            await changeRepositories(context, owner_id);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", owner_id);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(getRepositoryList).not.toHaveBeenCalled();
            expect(getForkedRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                selected_owner_id,
                "push_date",
                expect.any(Function)
            );
        });

        it("When plugin GitLab is used, Then gitlab repositories must be retrieved", async () => {
            const getGitlabRepositoryList = jest.spyOn(rest_querier, "getGitlabRepositoryList");
            const context = {
                commit: jest.fn(),
                getters: {
                    areRepositoriesAlreadyLoadedForCurrentOwner: false,
                    isFolderDisplayMode: false,
                    isGitlabUsed: true,
                },
            };
            mockFetchSuccess(getRepositoryList);
            mockFetchSuccess(getGitlabRepositoryList);

            await changeRepositories(context, PROJECT_KEY);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", PROJECT_KEY);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(getGitlabRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                "push_date",
                expect.any(Function)
            );

            expect(getRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                "push_date",
                expect.any(Function)
            );
            expect(getForkedRepositoryList).not.toHaveBeenCalled();
        });
    });

    describe("getAsyncRepositoryList", () => {
        let commit, getRepositories;
        beforeEach(() => {
            commit = jest.fn();
            getRepositories = jest.fn();
        });

        it("When I want to load the project repositories, Then it should fetch them asynchronously and put them in the store.", async () => {
            const repositories = [{ name: "VueX" }];
            getRepositories.mockImplementation((callback) => callback(repositories));

            await getAsyncRepositoryList(commit, getRepositories);

            expect(commit).toHaveBeenCalledWith("setIsLoadingInitial", true);
            expect(commit).toHaveBeenCalledWith("setIsLoadingNext", true);
            expect(commit).toHaveBeenCalledWith("pushRepositoriesForCurrentOwner", repositories);

            expect(commit).toHaveBeenCalledWith("setIsLoadingInitial", false);
            expect(commit).toHaveBeenCalledWith("setIsLoadingNext", false);
            expect(commit).toHaveBeenCalledWith("setIsFirstLoadDone", true);
        });

        it("When the server responds with a 404, then the error for 'No git service' will be committed", async () => {
            const error_json = {
                error: {
                    code: "404",
                },
            };
            mockFetchError(getRepositories, { error_json });

            await getAsyncRepositoryList(commit, getRepositories);

            expect(commit).toHaveBeenCalledWith("setErrorMessageType", ERROR_TYPE_NO_GIT);
        });

        it("When the server responds with another error code, then the unknown error will be committed", async () => {
            const error_json = {
                error: {
                    code: "403",
                },
            };
            mockFetchError(getRepositories, { error_json });

            await expect(getAsyncRepositoryList(commit, getRepositories)).rejects.toBeDefined();
            expect(commit).toHaveBeenCalledWith("setErrorMessageType", ERROR_TYPE_UNKNOWN_ERROR);
        });

        it("When something else happens (no response), then the unknown error will be committed", async () => {
            mockFetchError(getRepositories, { status: 500 });

            await expect(getAsyncRepositoryList(commit, getRepositories)).rejects.toBeDefined();
            expect(commit).toHaveBeenCalledWith("setErrorMessageType", ERROR_TYPE_UNKNOWN_ERROR);
        });
    });

    describe("getGitlabRepositoryList", () => {
        let context;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
            };
        });

        it("When api is called, Then url is formatted", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        headers: {
                            get: () => 1,
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            expect(await getGitlabRepositoryList(context, credentials)).toEqual([{ id: 10 }]);
            expect(getAsyncGitlabRepositoryList).toHaveBeenCalledWith({
                server_url:
                    "https://example/api/v4/projects?membership=true&per_page=20&min_access_level=40",
                token: "azerty1234",
            });
        });

        it("When there is 2 pages, Then api is called twice", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        headers: {
                            get: () => 2,
                        },
                        status: 200,
                        json: () => Promise.resolve([{ id: 10 }]),
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            expect(await getGitlabRepositoryList(context, credentials)).toEqual([
                { id: 10 },
                { id: 10 },
            ]);
            expect(getAsyncGitlabRepositoryList).toBeCalledTimes(2);
        });

        it("When en error retrieved from api, Then an error is thrown", async () => {
            const getAsyncGitlabRepositoryList = jest.spyOn(
                gitlab_querier,
                "getAsyncGitlabRepositoryList"
            );
            getAsyncGitlabRepositoryList.mockReturnValue(
                new Promise((resolve) => {
                    resolve({
                        status: 401,
                    });
                })
            );
            const credentials = {
                server_url: "https://example/",
                token: "azerty1234",
            };

            await expect(getGitlabRepositoryList(context, credentials)).rejects.toEqual(
                new Error()
            );
            expect(getAsyncGitlabRepositoryList).toHaveBeenCalledWith({
                server_url:
                    "https://example/api/v4/projects?membership=true&per_page=20&min_access_level=40",
                token: "azerty1234",
            });
        });
    });
});
