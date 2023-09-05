/**
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

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    PROJECT_KEY,
    REPOSITORIES_SORTED_BY_LAST_UPDATE,
    REPOSITORIES_SORTED_BY_PATH,
    ANONYMOUS_USER_ID,
} from "../constants";
import { setDisplayMode, getAsyncRepositoryList, changeRepositories } from "./actions";
import * as repository_list_presenter from "../repository-list-presenter";
import * as rest_querier from "../api/rest-querier";
import type { ActionContext } from "vuex";
import type { State } from "../type";

describe("Store actions", () => {
    describe("setDisplayMode", () => {
        let context: ActionContext<State, State>;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
            } as unknown as ActionContext<State, State>;
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

            const setRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "setRepositoriesSortedByPathUserPreference",
            );
            const deleteRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "deleteRepositoriesSortedByPathUserPreference",
            );

            expect(setRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
            expect(deleteRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
        });

        it("saves user preferences if by path", async () => {
            const getUserId = jest.spyOn(repository_list_presenter, "getUserId");
            getUserId.mockReturnValue(101);

            const setRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "setRepositoriesSortedByPathUserPreference",
            );
            const deleteRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "deleteRepositoriesSortedByPathUserPreference",
            );

            mockFetchSuccess(setRepositoriesSortedByPathUserPreference);

            const new_mode = REPOSITORIES_SORTED_BY_PATH;

            await setDisplayMode(context, new_mode);

            expect(setRepositoriesSortedByPathUserPreference).toHaveBeenCalledWith(101);
            expect(deleteRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
        });

        it("deletes user preferences if not by path", async () => {
            const getUserId = jest.spyOn(repository_list_presenter, "getUserId");
            getUserId.mockReturnValue(101);

            const setRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "setRepositoriesSortedByPathUserPreference",
            );
            const deleteRepositoriesSortedByPathUserPreference = jest.spyOn(
                rest_querier,
                "deleteRepositoriesSortedByPathUserPreference",
            );

            mockFetchSuccess(deleteRepositoriesSortedByPathUserPreference);

            const new_mode = REPOSITORIES_SORTED_BY_LAST_UPDATE;

            await setDisplayMode(context, new_mode);

            expect(deleteRepositoriesSortedByPathUserPreference).toHaveBeenCalledWith(101);
            expect(setRepositoriesSortedByPathUserPreference).not.toHaveBeenCalled();
        });
    });

    describe("changeRepositories", () => {
        const current_project_id = 100;

        let getRepositoryList: jest.SpyInstance,
            getForkedRepositoryList: jest.SpyInstance,
            getProjectId: jest.SpyInstance;

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
            } as unknown as ActionContext<State, State>;

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
            } as unknown as ActionContext<State, State>;

            mockFetchSuccess(getRepositoryList);

            await changeRepositories(context, PROJECT_KEY);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", PROJECT_KEY);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(getRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                "push_date",
                expect.any(Function),
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
            } as unknown as ActionContext<State, State>;

            mockFetchSuccess(getForkedRepositoryList);

            const owner_id = 101;

            await changeRepositories(context, owner_id);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", owner_id);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(getRepositoryList).not.toHaveBeenCalled();
            expect(getForkedRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                String(selected_owner_id),
                "push_date",
                expect.any(Function),
            );
        });

        it("When plugin GitLab is used, Then gitlab repositories must be retrieved", async () => {
            const context = {
                commit: jest.fn(),
                getters: {
                    areRepositoriesAlreadyLoadedForCurrentOwner: false,
                    isFolderDisplayMode: false,
                    isGitlabUsed: true,
                },
                dispatch: jest.fn(),
            } as unknown as ActionContext<State, State>;
            mockFetchSuccess(getRepositoryList);

            await changeRepositories(context, PROJECT_KEY);

            expect(context.commit).toHaveBeenCalledWith("setSelectedOwnerId", PROJECT_KEY);
            expect(context.commit).toHaveBeenCalledWith("setFilter", "");

            expect(context.dispatch).toHaveBeenCalledWith(
                "gitlab/getGitlabRepositories",
                "push_date",
                { root: true },
            );

            expect(getRepositoryList).toHaveBeenCalledWith(
                current_project_id,
                "push_date",
                expect.any(Function),
            );
            expect(getForkedRepositoryList).not.toHaveBeenCalled();
        });
    });

    describe("getAsyncRepositoryList", () => {
        it("When I want to load the project repositories, Then it should fetch them asynchronously and put them in the store.", async () => {
            const context = { commit: jest.fn() } as unknown as ActionContext<State, State>;
            const getRepositories = jest.fn();

            const repositories = [{ name: "VueX" }];
            getRepositories.mockImplementation((callback) => callback(repositories));

            await getAsyncRepositoryList(context, getRepositories);

            expect(context.commit).toHaveBeenCalledWith("setIsLoadingInitial", true);
            expect(context.commit).toHaveBeenCalledWith("setIsLoadingNext", true);
            expect(context.commit).toHaveBeenCalledWith(
                "pushRepositoriesForCurrentOwner",
                repositories,
            );

            expect(context.commit).toHaveBeenCalledWith("setIsLoadingInitial", false);
            expect(context.commit).toHaveBeenCalledWith("setIsLoadingNext", false);
            expect(context.commit).toHaveBeenCalledWith("setIsFirstLoadDone", true);
        });
    });
});
