/*
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
import { REPOSITORIES_SORTED_BY_LAST_UPDATE, ERROR_TYPE_NO_ERROR, PROJECT_KEY } from "../constants";
import type { State } from "../type";

const state: State = {
    repositories_for_owner: [
        {
            id: PROJECT_KEY,
            repositories: [],
        },
    ],
    filter: "",
    selected_owner_id: PROJECT_KEY,
    error_message_type: ERROR_TYPE_NO_ERROR,
    success_message: "",
    is_loading_initial: true,
    is_loading_next: true,
    add_repository_modal: null,
    display_mode: REPOSITORIES_SORTED_BY_LAST_UPDATE,
    is_first_load_done: false,
    services_name_used: [],
    add_gitlab_repository_modal: null,
    unlink_gitlab_repository_modal: null,
    unlink_gitlab_repository: null,
};
export default state;
