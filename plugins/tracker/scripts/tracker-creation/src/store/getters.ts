/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { CreationOptions, ProjectTemplate, State, Tracker } from "./type";
import { TRACKER_SHORTNAME_FORMAT } from "../constants";

export const is_ready_for_step_2 = (state: State): boolean => {
    if (state.active_option === CreationOptions.NONE_YET) {
        return false;
    }

    return (
        isDuplicationReady(state) ||
        isXmlImportReady(state) ||
        isEmptyReady(state) ||
        isDuplicationFromAnotherProjectReady(state)
    );
};

function isEmptyReady(state: State): boolean {
    return state.active_option === CreationOptions.TRACKER_EMPTY;
}

function isDuplicationReady(state: State): boolean {
    return (
        state.active_option === CreationOptions.TRACKER_TEMPLATE &&
        state.selected_tracker_template !== null
    );
}

function isDuplicationFromAnotherProjectReady(state: State): boolean {
    return (
        state.active_option === CreationOptions.TRACKER_ANOTHER_PROJECT &&
        state.selected_project_tracker_template !== null
    );
}

function isXmlImportReady(state: State): boolean {
    return (
        state.active_option === CreationOptions.TRACKER_XML_FILE &&
        state.is_a_xml_file_selected &&
        state.has_xml_file_error === false &&
        state.is_parsing_a_xml_file === false
    );
}
export const is_created_from_empty = (state: State): boolean => {
    return state.active_option === CreationOptions.TRACKER_EMPTY;
};

export const is_a_duplication = (state: State): boolean => {
    return state.active_option === CreationOptions.TRACKER_TEMPLATE;
};

export const is_a_duplication_of_a_tracker_from_another_project = (state: State): boolean => {
    return state.active_option === CreationOptions.TRACKER_ANOTHER_PROJECT;
};

export const is_a_xml_import = (state: State): boolean => {
    return state.active_option === CreationOptions.TRACKER_XML_FILE;
};

export const can_display_slugify_mode = (state: State): boolean => {
    return state.is_in_slugify_mode && state.active_option !== CreationOptions.TRACKER_XML_FILE;
};

export const is_shortname_valid = (state: State): boolean => {
    return TRACKER_SHORTNAME_FORMAT.test(state.tracker_to_be_created.shortname);
};

export const is_name_already_used = (state: State): boolean => {
    return (
        state.existing_trackers.names.indexOf(state.tracker_to_be_created.name.toLowerCase()) !== -1
    );
};

export const is_shortname_already_used = (state: State): boolean => {
    return (
        state.existing_trackers.shortnames.indexOf(
            state.tracker_to_be_created.shortname.toLowerCase()
        ) !== -1
    );
};

export const is_ready_to_submit = (state: State): boolean => {
    return (
        state.tracker_to_be_created.name.length > 0 &&
        state.tracker_to_be_created.shortname.length > 0 &&
        !is_name_already_used(state) &&
        !is_shortname_already_used(state) &&
        is_shortname_valid(state)
    );
};

export const project_of_selected_tracker_template = (state: State): ProjectTemplate | null => {
    const target_tracker = state.selected_tracker_template;
    if (target_tracker === null) {
        return null;
    }

    return (
        state.project_templates.find((project: ProjectTemplate) => {
            return Boolean(
                project.tracker_list.find((tracker: Tracker) => tracker.id === target_tracker.id)
            );
        }) || null
    );
};
