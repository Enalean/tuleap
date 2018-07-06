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

import { ERROR_TYPE_NO_ERROR } from "../constants.js";

export const filteredRepositories = state => {
    const repositories = state.repositories_for_owner[state.selected_owner_id] || [];

    return repositories.filter(repository => {
        return repository.name.toLowerCase().includes(state.filter.toLowerCase());
    });
};

export const areRepositoriesAlreadyLoadedForCurrentOwner = state => {
    return state.repositories_for_owner.hasOwnProperty(state.selected_owner_id);
};

export const isThereAtLeastOneRepository = state => {
    for (const owner_id in state.repositories_for_owner) {
        if (
            state.repositories_for_owner.hasOwnProperty(owner_id) &&
            state.repositories_for_owner[owner_id].length > 0
        ) {
            return true;
        }
    }

    return false;
};

export const hasError = state => {
    return state.error_message_type !== ERROR_TYPE_NO_ERROR;
};

export const isInitialLoadingDoneWithoutError = state => {
    return !state.is_loading_initial && !state.has_error;
};
