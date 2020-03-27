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

export interface State {
    csrf_token: CSRFToken;
    default_templates: Tracker[];
    project_templates: ProjectTemplate[];
    active_option: CreationOptions;
    selected_tracker_template: Tracker | null;
    selected_project_tracker_template: Tracker | null;
    selected_project: ProjectWithTrackers | null;
    selected_xml_file_input: HTMLInputElement | null;
    tracker_to_be_created: TrackerToBeCreatedMandatoryData;
    has_form_been_submitted: boolean;
    is_a_xml_file_selected: boolean;
    is_parsing_a_xml_file: boolean;
    has_xml_file_error: boolean;
    is_in_slugify_mode: boolean;
    existing_trackers: ExistingTrackersList;
    trackers_from_other_projects: ProjectWithTrackers[];
    project_id: number;
    color_picker_data: DataForColorPicker[];
    default_tracker_color: string;
    company_name: string;
}

export interface CSRFToken {
    name: string;
    value: string;
}

export interface ProjectTemplate {
    readonly project_name: string;
    readonly tracker_list: Tracker[];
}

export interface Tracker {
    readonly id: string;
    readonly name: string;
    readonly tlp_color: string;
}

export interface ProjectWithTrackers {
    readonly id: string;
    readonly name: string;
    readonly trackers: Tracker[];
}

export interface TrackerToBeCreatedMandatoryData {
    name: string;
    shortname: string;
    color: string;
}

export interface ExistingTrackersList {
    names: string[];
    shortnames: string[];
}

export interface DataForColorPicker {
    id: string;
    text: string;
}

export enum CreationOptions {
    NONE_YET = "none_yet",
    TRACKER_TEMPLATE = "tracker_template",
    TRACKER_XML_FILE = "tracker_xml_file",
    TRACKER_EMPTY = "tracker_empty",
    TRACKER_ANOTHER_PROJECT = "tracker_another_project",
}
