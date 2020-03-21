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

import state from "./state.js";

const initial_state = { ...state };

export function setSourceBranches(state, branches) {
    state.source_branches = branches;
}

export function setDestinationBranches(state, branches) {
    state.destination_branches = branches;
}

export function setCreateErrorMessage(state, create_error_message) {
    state.create_error_message = create_error_message;
}

export function setHasErrorWhileLoadingBranchesToTrue(state) {
    state.has_error_while_loading_branches = true;
}

export function setIsCreatinPullRequest(state, is_creating) {
    state.is_creating_pullrequest = is_creating;
}

export function resetSelection(state) {
    state.create_error_message = initial_state.create_error_message;
    state.selected_source_branch = initial_state.selected_source_branch;
    state.selected_destination_branch = initial_state.selected_destination_branch;
}

export function setSelectedSourceBranch(state, branch) {
    state.selected_source_branch = branch;
    state.create_error_message = initial_state.create_error_message;
}

export function setSelectedDestinationBranch(state, branch) {
    state.selected_destination_branch = branch;
    state.create_error_message = initial_state.create_error_message;
}
