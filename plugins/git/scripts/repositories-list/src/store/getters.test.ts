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

import * as getters from "./getters";
import {
    ERROR_TYPE_NO_ERROR,
    ERROR_TYPE_NO_GIT,
    PROJECT_KEY,
    REPOSITORIES_SORTED_BY_LAST_UPDATE,
    REPOSITORIES_SORTED_BY_PATH,
} from "../constants";
import type { Folder, State, RepositoriesForOwner, Repository } from "../type";
import * as filter from "../support/filter";

describe("Store getters", () => {
    describe("getFilteredRepositoriesByLastUpdateDate", () => {
        it("Given that the repositories are not yet loaded, then an empty array will be returned", () => {
            const state = {
                repositories_for_owner: {
                    101: [
                        {
                            label: "vuex",
                            name: "vuex",
                        } as Repository,
                    ],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
            } as State;

            const result = getters.getFilteredRepositoriesByLastUpdateDate(state);

            expect(result).toEqual([]);
        });

        it("Given project repositories are loaded, then it will return an array sorted by last update date descending", () => {
            const first_repo = {
                normalized_path: "sequacious/missis",
                last_update_date: "2019-12-08T07:58:37+01:00",
            } as Repository;
            const last_repo = {
                normalized_path: "putridity",
                last_update_date: "2021-03-24T01:35:50+01:00",
            } as Repository;

            const state = {
                repositories_for_owner: {
                    101: [first_repo, last_repo],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
                filter: "",
            } as State;

            const result = getters.getFilteredRepositoriesByLastUpdateDate(state);

            expect(result).toEqual([last_repo, first_repo]);
        });
    });

    describe("getFilteredRepositoriesGroupedByPath", () => {
        it("Given that the repositories are not yet loaded, then an empty structure will be returned", () => {
            const state = {
                repositories_for_owner: {
                    101: [],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
            } as State;

            const result = getters.getFilteredRepositoriesGroupedByPath(state);

            expect(result).toEqual({
                is_folder: true,
                label: "root",
                children: [],
            });
        });

        it("Given project repositories are loaded, then it will return a tree structure sorted by path", () => {
            const project_repository_at_root = {
                path_without_project: "",
                label: "veiledness",
                normalized_path: "veiledness",
            } as Repository;
            const project_repository_with_path = {
                path_without_project: "sardanapalus/goatish",
                label: "solidification",
                normalized_path: "sardanapalus/goatish/solidification",
            } as Repository;
            const other_repo_with_path = {
                path_without_project: "sardanapalus",
                label: "perform",
                normalized_path: "sardanapalus/perform",
            } as Repository;

            const state = {
                repositories_for_owner: {
                    101: [
                        project_repository_with_path,
                        project_repository_at_root,
                        other_repo_with_path,
                    ],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
                filter: "",
            } as State;

            const result = getters.getFilteredRepositoriesGroupedByPath(state);

            expect(result.is_folder).toBe(true);
            expect(result.label).toBe("root");
            expect(result.children).toHaveLength(2);
            const [first_folder, root_repo] = result.children;
            if (!("is_folder" in first_folder)) {
                throw Error("Expected a folder");
            }
            expect(first_folder.label).toBe("sardanapalus");
            expect(root_repo).toBe(project_repository_at_root);
            expect(first_folder.children).toHaveLength(2);
            const [project_path_folder, other_leaf_repo] = first_folder.children;
            if (!("is_folder" in project_path_folder)) {
                throw Error("Expected a folder");
            }
            expect(project_path_folder.label).toBe("goatish");
            expect(other_leaf_repo).toBe(other_repo_with_path);

            expect(project_path_folder.children).toHaveLength(1);
            const leaf_repo = project_path_folder.children[0];
            expect(leaf_repo).toBe(project_repository_with_path);
        });

        it("Given forked repositories are loaded, then it will return a tree structure sorted by path", () => {
            const forked_repository = {
                path_without_project: "u/jveloso",
                label: "unpleadable",
                normalized_path: "u/jveloso/unpleadable",
            } as Repository;

            const state = {
                repositories_for_owner: {
                    101: [forked_repository],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
                filter: "",
            } as State;

            const result = getters.getFilteredRepositoriesGroupedByPath(state);

            expect(result).toEqual({
                is_folder: true,
                label: "root",
                children: expect.any(Array),
            });

            if (!("children" in result) || !(result.children instanceof Array)) {
                throw new Error("result does not have any children");
            }

            expect(result.children).toHaveLength(1);
            const forks_folder = result.children[0];
            expect(forks_folder).toEqual({
                is_folder: true,
                label: "u",
                children: expect.any(Array),
            });

            if (!("children" in forks_folder) || !(forks_folder.children instanceof Array)) {
                throw new Error("forks folder does not have any children");
            }
            expect(forks_folder.children).toHaveLength(1);
            const user_folder = forks_folder.children[0];
            expect(user_folder).toEqual({
                is_folder: true,
                label: "jveloso",
                children: expect.any(Array),
            });
            if (!("children" in user_folder) || !(user_folder.children instanceof Array)) {
                throw new Error("user_folder does not have any children");
            }
            const fork_leaf_repo = user_folder.children[0];
            expect(fork_leaf_repo).toEqual(forked_repository);
        });

        it("Given repositories, then it will sort folders before repositories and then sort each group alphabetically", () => {
            const root_repository = {
                path_without_project: "",
                label: "acquirability",
                normalized_path: "acquirability",
            } as Repository;
            const project_repository_with_path = {
                path_without_project: "zannichelliaceae",
                label: "kafta",
                normalized_path: "zannichelliaceae/kafta",
            } as Repository;
            const state = {
                repositories_for_owner: {
                    101: [root_repository, project_repository_with_path],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
                filter: "",
            } as State;

            const result = getters.getFilteredRepositoriesGroupedByPath(state);

            const [folder, root_repo] = result.children;
            if (!("label" in folder)) {
                throw new Error("folder is not a Folder");
            }
            expect(folder.label).toBe("zannichelliaceae");
            if (!("label" in root_repo)) {
                throw new Error("root_repo is not a Folder");
            }
            expect(root_repo.label).toBe("acquirability");
        });

        it("Given a filter query and repositories, then it will keep only repositories with the query in their normalized path and it will keep only folders that are not empty as a result", () => {
            const project_repository_at_root = {
                path_without_project: "",
                label: "soldiering",
                normalized_path: "soldiering",
            } as Repository;
            const project_repository_with_path = {
                path_without_project: "sardanapalus/goatish",
                label: "solidification",
                normalized_path: "sardanapalus/goatish/solidification",
            } as Repository;
            const other_repo_with_path = {
                path_without_project: "sardanapalus",
                label: "perform",
                normalized_path: "sardanapalus/perform",
            } as Repository;

            const state = {
                repositories_for_owner: {
                    101: [
                        project_repository_with_path,
                        project_repository_at_root,
                        other_repo_with_path,
                    ],
                } as RepositoriesForOwner,
                selected_owner_id: 101,
                filter: "sol",
            } as State;

            const result = getters.getFilteredRepositoriesGroupedByPath(state);

            if (!("children" in result) || !(result.children instanceof Array)) {
                throw new Error("result does not have any children");
            }
            expect(result.children).toHaveLength(2);
            const [first_folder, root_repo] = result.children;

            expect(root_repo).toEqual(project_repository_at_root);
            if (!("children" in first_folder) || !(first_folder.children instanceof Array)) {
                throw new Error("first_folder does not have any children");
            }
            expect(first_folder.children).toHaveLength(1);
            const path_repo = first_folder.children[0];

            if (!("children" in path_repo) || !(path_repo.children instanceof Array)) {
                throw new Error("path_repo does not have any children");
            }
            expect(path_repo.children).toHaveLength(1);
            const leaf_repo = path_repo.children[0];

            expect(leaf_repo).toEqual(project_repository_with_path);
        });
    });
    //
    describe("areRepositoriesAlreadyLoadedForCurrentOwner", () => {
        it("will return false when there is no 'project' key", () => {
            const state = {
                repositories_for_owner: {} as RepositoriesForOwner,
                selected_owner_id: PROJECT_KEY,
            } as State;

            const result = getters.areRepositoriesAlreadyLoadedForCurrentOwner(state);

            expect(result).toBe(false);
        });

        it("will return true when there is a key matching the selected 'owner' user id (the person who forked repositories)", () => {
            const state = {
                repositories_for_owner: {
                    887: [],
                } as RepositoriesForOwner,
                selected_owner_id: 887,
            } as State;

            const result = getters.areRepositoriesAlreadyLoadedForCurrentOwner(state);

            expect(result).toBe(true);
        });
    });

    describe("isInitialLoadingDoneWithoutError", () => {
        it("will return true when initial loading is done and there is no error", () => {
            const state = {
                is_loading_initial: false,
                error_message_type: ERROR_TYPE_NO_ERROR,
            } as State;

            const result = getters.isInitialLoadingDoneWithoutError(state);
            expect(result).toBe(true);
        });

        it("will return false when initial loading is not done", () => {
            const state = {
                is_loading_initial: true,
                error_message_type: ERROR_TYPE_NO_ERROR,
            } as State;
            const result = getters.isInitialLoadingDoneWithoutError(state);
            expect(result).toBe(false);
        });

        it("will return false when there is an error", () => {
            const state = {
                is_loading_initial: false,
                error_message_type: ERROR_TYPE_NO_GIT,
            } as State;

            const result = getters.isInitialLoadingDoneWithoutError(state);
            expect(result).toBe(false);
        });
    });

    describe("isLoading", () => {
        it("will return true when initial loading is true", () => {
            const state = {
                is_loading_initial: true,
                is_loading_next: false,
            } as State;

            expect(getters.isLoading(state)).toBe(true);
        });

        it("will return true when 'next batch' loading is true", () => {
            const state = {
                is_loading_initial: false,
                is_loading_next: true,
            } as State;

            expect(getters.isLoading(state)).toBe(true);
        });

        it("will return false when initial loading and 'next batch' loading are both done", () => {
            const state = {
                is_loading_initial: false,
                is_loading_next: false,
            } as State;

            expect(getters.isLoading(state)).toBe(false);
        });
    });

    describe("isThereAResultInCurrentFilteredList", () => {
        it("Given current display mode is 'sorted by path', then it will return true if the root folder has children", () => {
            const folder = {
                is_folder: true,
                label: "root",
                children: [{ label: "whulk" } as Repository],
            } as Folder;
            const state = {
                display_mode: REPOSITORIES_SORTED_BY_PATH,
                repositories_for_owner: {
                    887: [folder],
                } as RepositoriesForOwner,
                selected_owner_id: 887,
            } as State;

            jest.spyOn(filter, "filterAFolder").mockReturnValue(folder);

            const result = getters.isThereAResultInCurrentFilteredList(state);
            expect(result).toBe(true);
        });

        it("Given current display mode is 'sorted by last update date', then it will return true if the filtered array has at least one repository", () => {
            const folder = {
                label: "prosternum",
                normalized_path: "test/prosternum",
            } as Folder;
            const state = {
                display_mode: REPOSITORIES_SORTED_BY_LAST_UPDATE,
                repositories_for_owner: {
                    887: [folder],
                } as RepositoriesForOwner,
                selected_owner_id: 887,
                filter: "pro",
            } as State;

            const result = getters.isThereAResultInCurrentFilteredList(state);
            expect(result).toBe(true);
        });
    });

    describe("getGitlabRepositoriesIntegrated", () => {
        it("will return all Gitlab Repository", () => {
            const git_repository = {
                label: "vuex",
                name: "vuex",
                path: "myproject/vuex.git",
                path_without_project: "",
                normalized_path: "vuex",
            } as Repository;

            const gitlab_repository = {
                id: "gitlab_1",
                integration_id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/MyPath/MyRepo",
                    gitlab_repository_id: 1,
                },
            } as Repository;

            const state = {
                display_mode: REPOSITORIES_SORTED_BY_LAST_UPDATE,
                repositories_for_owner: {
                    887: [git_repository, gitlab_repository],
                } as RepositoriesForOwner,
                selected_owner_id: 887,
                filter: "",
            } as State;

            const result = getters.getGitlabRepositoriesIntegrated(state);
            expect(result).toEqual([gitlab_repository]);
        });
    });
});
