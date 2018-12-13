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

export { clearModalShown, showModal, initUserGroups, endLoadingModal, failModalOperation };

function showModal(state, transition) {
    state.is_modal_shown = true;
    state.is_loading_modal = true;
    state.current_transition = transition;
}

function clearModalShown(state) {
    state.is_modal_shown = false;
}

function initUserGroups(state, user_groups) {
    state.user_groups = user_groups;
}

function endLoadingModal(state) {
    state.is_loading_modal = false;
}
function failModalOperation(state, message) {
    state.is_modal_operation_failed = true;
    state.modal_operation_failure_message = message;
}
