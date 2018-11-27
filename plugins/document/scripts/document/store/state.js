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

const state = {
    project_root_document_id: null,
    project_id: null,
    is_loading_folder: true,
    has_folder_permission_error: false,
    has_folder_loading_error: false,
    folder_loading_error: null,
    project_name: null,
    is_user_administrator: false,
    folder_content: [],
    date_time_format: null,
    current_folder_title: null,
    is_loading_folder_title: false
};

export default state;
