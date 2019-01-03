/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

export { is_transition_from_new_artifact, post_actions };

function is_transition_from_new_artifact(state) {
    return state.current_transition !== null ? state.current_transition.from_id === null : false;
}

function post_actions(state) {
    if (!state.post_actions_by_unique_id) {
        return null;
    }
    return Object.keys(state.post_actions_by_unique_id).map(
        unique_id => state.post_actions_by_unique_id[unique_id]
    );
}
