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
    active_option: CreationOptions | string;
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
    from_jira_data: JiraImportData;
    are_there_tv3: boolean;
    project_unix_name: string;
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
    readonly description: string;
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
    description: string;
}

export interface ExistingTrackersList {
    names: string[];
    shortnames: string[];
}

export interface DataForColorPicker {
    id: string;
    text: string;
}

export interface JiraImportData {
    credentials: Credentials | null;
    project: ProjectList | null;
    tracker: TrackerList | null;
    project_list: ProjectList[] | null;
    tracker_list: TrackerList[] | null;
}

export interface Credentials {
    server_url: string;
    user_email: string;
    token: string;
}

export interface ProjectList {
    id: string;
    label: string;
}

export interface TrackerList {
    id: string;
    name: string;
}

export interface ProjectTrackerPayload {
    credentials: Credentials;
    project_key: string;
}

export type CreationOptions =
    | "none_yet"
    | "tracker_template"
    | "tracker_xml_file"
    | "tracker_empty"
    | "tracker_another_project"
    | "from_jira";
export const NONE_YET: CreationOptions = "none_yet";
export const TRACKER_TEMPLATE: CreationOptions = "tracker_template";
export const TRACKER_XML_FILE: CreationOptions = "tracker_xml_file";
export const TRACKER_EMPTY: CreationOptions = "tracker_empty";
export const TRACKER_ANOTHER_PROJECT: CreationOptions = "tracker_another_project";
export const FROM_JIRA: CreationOptions = "from_jira";
