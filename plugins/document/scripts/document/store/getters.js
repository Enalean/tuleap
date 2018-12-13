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

export const does_folder_have_any_error = state =>
    state.has_folder_permission_error || state.has_folder_loading_error;

export const is_folder_empty = state => state.folder_content.length === 0;

export const current_folder_title = state => {
    const hierarchy = state.current_folder_ascendant_hierarchy;

    if (hierarchy.length === 0) {
        return state.root_title;
    }

    return hierarchy[hierarchy.length - 1].title;
};
