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

import type { RootState } from "../type";

export const state: RootState = {
    configuration: {
        is_obsolescence_date_property_used: false,
        max_files_dragndrop: 0,
        max_size_upload: 0,
        warning_threshold: 0,
        max_archive_size: 0,
        project_url: "",
        date_time_format: "",
        privacy: {
            are_restricted_users_allowed: false,
            project_is_private: false,
            project_is_public_incl_restricted: false,
            project_is_public: false,
            project_is_private_incl_restricted: false,
            explanation_text: "",
            privacy_title: "",
        },
        project_flags: [],
        is_changelog_proposed_after_dnd: false,
        is_deletion_allowed: false,
        user_locale: "",
        relative_dates_display: "absolute_first-relative_tooltip",
        project_icon: "",
        criteria: [],
        columns: [],
        forbid_writers_to_update: false,
        forbid_writers_to_delete: false,
        filename_pattern: "",
        is_filename_pattern_enforced: false,
        can_user_switch_to_old_ui: false,
    },
    error: {
        has_document_permission_error: false,
        has_document_loading_error: false,
        document_loading_error: null,
        has_folder_permission_error: false,
        has_folder_loading_error: false,
        folder_loading_error: null,
        has_modal_error: false,
        modal_error: null,
        has_document_lock_error: false,
        document_lock_error: null,
        has_global_modal_error: false,
        global_modal_error_message: null,
    },
    permissions: {
        project_ugroups: null,
    },
    properties: {
        project_properties: [],
        has_loaded_properties: false,
    },
    project_ugroups: null,
    is_loading_folder: true,
    folder_content: [],
    current_folder: null,
    current_folder_ascendant_hierarchy: [],
    is_loading_ascendant_hierarchy: false,
    root_title: "",
    folded_items_ids: [],
    folded_by_map: {},
    files_uploads_list: [],
    is_loading_currently_previewed_item: false,
    currently_previewed_item: null,
    show_post_deletion_notification: false,
    toggle_quick_look: false,
};
