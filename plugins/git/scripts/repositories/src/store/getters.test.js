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

import * as getters from "./getters.js";
import initial_state from "./state.js";
import { PROJECT_KEY } from "../constants.js";

describe("Store getters", () => {
    let state, mock_getters;
    beforeEach(() => {
        mock_getters = {
            areRepositoriesAlreadyLoadedForCurrentOwner: true,
            currentRepositoryList: [],
        };

        state = { ...initial_state };
    });

    describe("filteredRepositoriesByLastUpdateDate", () => {
        it("Given that the repositories are not yet loaded, then an empty array will be returned", () => {
            mock_getters.areRepositoriesAlreadyLoadedForCurrentOwner = false;

            const result = getters.filteredRepositoriesByLastUpdateDate(state, mock_getters);

            expect(result).toEqual([]);
        });

        it("Given project repositories are loaded, then it will return an array sorted by last update date descending", () => {
            const first_repo = {
                normalized_path: "sequacious/missis",
                last_update_date: "2019-12-08T07:58:37+01:00",
            };
            const last_repo = {
                normalized_path: "putridity",
                last_update_date: "2021-03-24T01:35:50+01:00",
            };

            mock_getters.currentRepositoryList = [first_repo, last_repo];

            const result = getters.filteredRepositoriesByLastUpdateDate(state, mock_getters);

            expect(result).toEqual([last_repo, first_repo]);
        });
    });

    describe("filteredRepositoriesGroupedByPath", () => {
        it("Given that the repositories are not yet loaded, then an empty structure will be returned", () => {
            mock_getters.areRepositoriesAlreadyLoadedForCurrentOwner = false;

            const result = getters.filteredRepositoriesGroupedByPath(state, mock_getters);

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
            };
            const project_repository_with_path = {
                path_without_project: "sardanapalus/goatish",
                label: "solidification",
                normalized_path: "sardanapalus/goatish/solidification",
            };
            const other_repo_with_path = {
                path_without_project: "sardanapalus",
                label: "perform",
                normalized_path: "sardanapalus/perform",
            };

            mock_getters.currentRepositoryList = [
                project_repository_with_path,
                project_repository_at_root,
                other_repo_with_path,
            ];

            const result = getters.filteredRepositoriesGroupedByPath(state, mock_getters);

            expect(result).toEqual({
                is_folder: true,
                label: "root",
                children: expect.any(Array),
            });

            expect(result.children.length).toEqual(2);
            const [first_folder, root_repo] = result.children;
            expect(first_folder).toEqual({
                is_folder: true,
                label: "sardanapalus",
                children: expect.any(Array),
            });
            expect(root_repo).toEqual(project_repository_at_root);

            expect(first_folder.children.length).toEqual(2);
            const [project_path_folder, other_leaf_repo] = first_folder.children;
            expect(project_path_folder).toEqual({
                is_folder: true,
                label: "goatish",
                children: expect.any(Array),
            });
            expect(other_leaf_repo).toEqual(other_repo_with_path);

            expect(project_path_folder.children.length).toEqual(1);
            const leaf_repo = project_path_folder.children[0];
            expect(leaf_repo).toEqual(project_repository_with_path);
        });

        it("Given forked repositories are loaded, then it will return a tree structure sorted by path", () => {
            const forked_repository = {
                path_without_project: "u/jveloso",
                label: "unpleadable",
                normalized_path: "u/jveloso/unpleadable",
            };

            mock_getters.currentRepositoryList = [forked_repository];

            const result = getters.filteredRepositoriesGroupedByPath(state, mock_getters);

            expect(result).toEqual({
                is_folder: true,
                label: "root",
                children: expect.any(Array),
            });

            expect(result.children.length).toEqual(1);
            const forks_folder = result.children[0];
            expect(forks_folder).toEqual({
                is_folder: true,
                label: "u",
                children: expect.any(Array),
            });

            expect(forks_folder.children.length).toEqual(1);
            const user_folder = forks_folder.children[0];
            expect(user_folder).toEqual({
                is_folder: true,
                label: "jveloso",
                children: expect.any(Array),
            });
            const fork_leaf_repo = user_folder.children[0];
            expect(fork_leaf_repo).toEqual(forked_repository);
        });

        it("Given repositories, then it will sort folders before repositories and then sort each group alphabetically", () => {
            const root_repository = {
                path_without_project: "",
                label: "acquirability",
                normalized_path: "acquirability",
            };
            const project_repository_with_path = {
                path_without_project: "zannichelliaceae",
                label: "kafta",
                normalized_path: "zannichelliaceae/kafta",
            };
            mock_getters.currentRepositoryList = [root_repository, project_repository_with_path];

            const result = getters.filteredRepositoriesGroupedByPath(state, mock_getters);

            const [folder, root_repo] = result.children;
            expect(folder.label).toEqual("zannichelliaceae");
            expect(root_repo.label).toEqual("acquirability");
        });

        it("Given a filter query and repositories, then it will keep only repositories with the query in their normalized path and it will keep only folders that are not empty as a result", () => {
            const project_repository_at_root = {
                path_without_project: "",
                label: "soldiering",
                normalized_path: "soldiering",
            };
            const project_repository_with_path = {
                path_without_project: "sardanapalus/goatish",
                label: "solidification",
                normalized_path: "sardanapalus/goatish/solidification",
            };
            const other_repo_with_path = {
                path_without_project: "sardanapalus",
                label: "perform",
                normalized_path: "sardanapalus/perform",
            };

            mock_getters.currentRepositoryList = [
                project_repository_with_path,
                project_repository_at_root,
                other_repo_with_path,
            ];
            state.filter = "sol";

            const result = getters.filteredRepositoriesGroupedByPath(state, mock_getters);

            expect(result.children.length).toEqual(2);
            const [first_folder, root_repo] = result.children;

            expect(root_repo).toEqual(project_repository_at_root);
            expect(first_folder.children.length).toEqual(1);
            const path_repo = first_folder.children[0];

            expect(path_repo.children.length).toEqual(1);
            const leaf_repo = path_repo.children[0];

            expect(leaf_repo).toEqual(project_repository_with_path);
        });
    });

    describe("areRepositoriesAlreadyLoadedForCurrentOwner", () => {
        it("will return false when there is no 'project' key", () => {
            state.selected_owner_id = PROJECT_KEY;
            state.repositories_for_owner = {};

            const result = getters.areRepositoriesAlreadyLoadedForCurrentOwner(state);

            expect(result).toEqual(false);
        });

        it("will return true when there is a key matching the selected 'owner' user id (the person who forked repositories)", () => {
            state.selected_owner_id = 887;
            state.repositories_for_owner[887] = [];

            const result = getters.areRepositoriesAlreadyLoadedForCurrentOwner(state);

            expect(result).toEqual(true);
        });
    });

    describe("isInitialLoadingDoneWithoutError", () => {
        it("will return true when initial loading is done and there is no error", () => {
            state.is_loading_initial = false;
            mock_getters.hasError = false;

            const result = getters.isInitialLoadingDoneWithoutError(state, mock_getters);
            expect(result).toEqual(true);
        });

        it("will return false when initial loading is not done", () => {
            mock_getters.hasError = false;

            const result = getters.isInitialLoadingDoneWithoutError(state, mock_getters);
            expect(result).toEqual(false);
        });

        it("will return false when there is an error", () => {
            mock_getters.hasError = true;

            const result = getters.isInitialLoadingDoneWithoutError(state, mock_getters);
            expect(result).toEqual(false);
        });
    });

    describe("isLoading", () => {
        it("will return true when initial loading is true", () => {
            state.is_loading_initial = true;
            state.is_loading_next = false;

            expect(getters.isLoading(state)).toEqual(true);
        });

        it("will return true when 'next batch' loading is true", () => {
            state.is_loading_initial = false;
            state.is_loading_next = true;

            expect(getters.isLoading(state)).toEqual(true);
        });

        it("will return false when initial loading and 'next batch' loading are both done", () => {
            state.is_loading_initial = false;
            state.is_loading_next = false;

            expect(getters.isLoading(state)).toEqual(false);
        });
    });

    describe("isThereAResultInCurrentFilteredList", () => {
        it("Given current display mode is 'sorted by path', then it will return true if the root folder has children", () => {
            mock_getters.isFolderDisplayMode = true;
            mock_getters.filteredRepositoriesGroupedByPath = {
                is_folder: true,
                label: "root",
                children: [{ label: "whulk" }],
            };

            const result = getters.isThereAResultInCurrentFilteredList(state, mock_getters);
            expect(result).toEqual(true);
        });

        it("Given current display mode is 'sorted by last update date', then it will return true if the filtered array has at least one repository", () => {
            mock_getters.isFolderDisplayMode = false;
            mock_getters.filteredRepositoriesByLastUpdateDate = [{ label: "prosternum" }];

            const result = getters.isThereAResultInCurrentFilteredList(state, mock_getters);
            expect(result).toEqual(true);
        });
    });
});
