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

import type { ProjectTemplate, State, Tracker } from "./type";
import {
    FROM_JIRA,
    NONE_YET,
    TRACKER_ANOTHER_PROJECT,
    TRACKER_EMPTY,
    TRACKER_TEMPLATE,
    TRACKER_XML_FILE,
} from "./type";
import { TRACKER_SHORTNAME_FORMAT } from "../constants";
import { isDefaultTemplateSelected } from "./is-default-template-selected";

export const is_ready_for_step_2 = (state: State): boolean => {
    if (state.active_option === NONE_YET) {
        return false;
    }

    return (
        isDuplicationReady(state) ||
        isXmlImportReady(state) ||
        isEmptyReady(state) ||
        isDuplicationFromAnotherProjectReady(state) ||
        isDefaultTemplateSelected(state) ||
        isJiraImportReady(state)
    );
};

function isEmptyReady(state: State): boolean {
    return state.active_option === TRACKER_EMPTY;
}

function isDuplicationReady(state: State): boolean {
    return state.active_option === TRACKER_TEMPLATE && state.selected_tracker_template !== null;
}

function isDuplicationFromAnotherProjectReady(state: State): boolean {
    return (
        state.active_option === TRACKER_ANOTHER_PROJECT &&
        state.selected_project_tracker_template !== null
    );
}

function isXmlImportReady(state: State): boolean {
    return (
        state.active_option === TRACKER_XML_FILE &&
        state.is_a_xml_file_selected &&
        state.has_xml_file_error === false &&
        state.is_parsing_a_xml_file === false
    );
}

function isJiraImportReady(state: State): boolean {
    return (
        state.active_option === FROM_JIRA &&
        state.from_jira_data.project !== null &&
        state.from_jira_data.tracker !== null
    );
}

export const is_created_from_default_template = (state: State): boolean => {
    return isDefaultTemplateSelected(state);
};

export const is_created_from_empty = (state: State): boolean => {
    return state.active_option === TRACKER_EMPTY;
};

export const is_a_duplication = (state: State): boolean => {
    return state.active_option === TRACKER_TEMPLATE;
};

export const is_a_duplication_of_a_tracker_from_another_project = (state: State): boolean => {
    return state.active_option === TRACKER_ANOTHER_PROJECT;
};

export const is_a_xml_import = (state: State): boolean => {
    return state.active_option === TRACKER_XML_FILE;
};

export const is_created_from_jira = (state: State): boolean => {
    return state.active_option === FROM_JIRA;
};

export const can_display_slugify_mode = (state: State): boolean => {
    return state.is_in_slugify_mode && state.active_option !== TRACKER_XML_FILE;
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
            state.tracker_to_be_created.shortname.toLowerCase(),
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
                project.tracker_list.find((tracker: Tracker) => tracker.id === target_tracker.id),
            );
        }) || null
    );
};
