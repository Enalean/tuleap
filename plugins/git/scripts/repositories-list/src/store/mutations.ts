/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import Vue from "vue";
import { REPOSITORIES_SORTED_BY_LAST_UPDATE, REPOSITORIES_SORTED_BY_PATH } from "../constants";
import { formatRepository } from "../gitlab/gitlab-repository-formatter";
import type { FormattedGitLabRepository, GitLabRepository, Repository, State } from "../type";
import type { Modal } from "@tuleap/tlp-modal";

export default {
    setSelectedOwnerId(state: State, selected_owner_id: string | number): void {
        state.selected_owner_id = selected_owner_id;
    },
    pushRepositoriesForCurrentOwner(state: State, repositories: Array<Repository>): void {
        if (typeof state.repositories_for_owner[state.selected_owner_id] === "undefined") {
            Vue.set(state.repositories_for_owner, state.selected_owner_id, []);
        }
        if (repositories.length > 0) {
            repositories.forEach(extendRepository);
            state.repositories_for_owner[state.selected_owner_id].push(...repositories);
        }
    },
    pushGitlabRepositoriesForCurrentOwner(
        state: State,
        repositories: Array<GitLabRepository>,
    ): void {
        if (typeof state.repositories_for_owner[state.selected_owner_id] === "undefined") {
            Vue.set(state.repositories_for_owner, state.selected_owner_id, []);
        }
        if (repositories.length > 0) {
            const repositories_formatted = repositories.map((repo: GitLabRepository) =>
                formatRepository(repo),
            );
            state.repositories_for_owner[state.selected_owner_id].push(...repositories_formatted);
        }
    },
    setFilter(state: State, filter: string): void {
        state.filter = filter;
    },
    setErrorMessageType(state: State, error_message_type: number): void {
        state.error_message_type = error_message_type;
    },
    setSuccessMessage(state: State, success_message: string): void {
        state.success_message = success_message;
    },
    setIsLoadingInitial(state: State, is_loading_initial: boolean): void {
        state.is_loading_initial = is_loading_initial;
    },
    setIsLoadingNext(state: State, is_loading_next: boolean): void {
        state.is_loading_next = is_loading_next;
    },
    setAddRepositoryModal(state: State, modal: Modal): void {
        state.add_repository_modal = modal;
    },
    setDisplayMode(state: State, new_mode: string): void {
        if (isUnknownMode(new_mode)) {
            state.display_mode = REPOSITORIES_SORTED_BY_LAST_UPDATE;
        } else {
            state.display_mode = new_mode;
        }
    },
    setIsFirstLoadDone(state: State, is_first_load_done: boolean): void {
        state.is_first_load_done = is_first_load_done;
    },
    setServicesNameUsed(state: State, services_name_used: Array<string>): void {
        state.services_name_used = services_name_used;
    },
    removeRepository(state: State, repository: Repository | FormattedGitLabRepository): void {
        const index_of_repository =
            state.repositories_for_owner[state.selected_owner_id].indexOf(repository);
        state.repositories_for_owner[state.selected_owner_id].splice(index_of_repository, 1);
    },
    resetRepositories(state: State): void {
        state.repositories_for_owner = {};
    },
};

function isUnknownMode(mode: string): boolean {
    return mode !== REPOSITORIES_SORTED_BY_LAST_UPDATE && mode !== REPOSITORIES_SORTED_BY_PATH;
}

function extendRepository(repository: Repository): void {
    repository.normalized_path =
        repository.path_without_project !== ""
            ? repository.path_without_project + "/" + repository.label
            : repository.label;
}
