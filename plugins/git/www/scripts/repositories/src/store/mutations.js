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

import Vue from "vue";
import { REPOSITORIES_SORTED_BY_LAST_UPDATE, REPOSITORIES_SORTED_BY_PATH } from "../constants.js";

export default {
    setSelectedOwnerId(state, selected_owner_id) {
        state.selected_owner_id = selected_owner_id;
    },
    pushRepositoriesForCurrentOwner(state, repositories) {
        if (typeof state.repositories_for_owner[state.selected_owner_id] === "undefined") {
            Vue.set(state.repositories_for_owner, state.selected_owner_id, []);
        }
        if (repositories.length > 0) {
            repositories.forEach(extendRepository);
            state.repositories_for_owner[state.selected_owner_id].push(...repositories);
        }
    },
    setFilter(state, filter) {
        state.filter = filter;
    },
    setErrorMessageType(state, error_message_type) {
        state.error_message_type = error_message_type;
    },
    setIsLoadingInitial(state, is_loading_initial) {
        state.is_loading_initial = is_loading_initial;
    },
    setIsLoadingNext(state, is_loading_next) {
        state.is_loading_next = is_loading_next;
    },
    setAddRepositoryModal(state, modal) {
        state.add_repository_modal = modal;
    },
    toggleDisplayMode(state) {
        state.display_mode =
            state.display_mode === REPOSITORIES_SORTED_BY_LAST_UPDATE
                ? REPOSITORIES_SORTED_BY_PATH
                : REPOSITORIES_SORTED_BY_LAST_UPDATE;
    }
};

function extendRepository(repository) {
    const split_path = repository.path.split("/");
    split_path.shift();
    repository.label = split_path.pop().replace(".git", "");
    repository.path_without_project = split_path.join("/");
}
