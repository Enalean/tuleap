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

import type {
    CreationOptions,
    Credentials,
    ProjectList,
    ProjectWithTrackers,
    State,
    Tracker,
    TrackerList,
    TrackerToBeCreatedMandatoryData,
} from "./type";
import { extractNameAndShortnameFromXmlFile } from "../helpers/xml-data-extractor";
import { getSlugifiedShortname } from "../helpers/shortname-slugifier";
import { isDefaultTemplateSelected } from "./is-default-template-selected";

export function setActiveOption(state: State, option: CreationOptions | string): void {
    state.active_option = option;
    state.selected_tracker_template = null;

    if (isDefaultTemplateSelected(state)) {
        setSelectedTrackerTemplate(state, option);
    }
}

export function setSelectedTrackerTemplate(state: State, tracker_id: string): void {
    let tracker: Tracker | undefined;

    for (let i = 0; !tracker && i < state.project_templates.length; i++) {
        tracker = state.project_templates[i].tracker_list.find(
            (tracker: Tracker) => tracker.id === tracker_id,
        );
    }

    if (!tracker) {
        tracker = state.default_templates.find((tracker) => tracker.id === tracker_id);
    }

    if (!tracker) {
        throw new Error("Tracker not found in store");
    }

    state.selected_tracker_template = tracker;
}

export function setSelectedProjectTrackerTemplate(state: State, tracker: Tracker | null): void {
    state.selected_project_tracker_template = tracker;
}

export function initTrackerNameWithTheSelectedTemplateName(state: State): void {
    if (!state.selected_tracker_template) {
        return;
    }

    initTrackerToBeCreatedWithSelectedTracker(state, state.selected_tracker_template);
}

export function initTrackerNameWithTheSelectedProjectTrackerTemplateName(state: State): void {
    if (!state.selected_project_tracker_template) {
        return;
    }

    initTrackerToBeCreatedWithSelectedTracker(state, state.selected_project_tracker_template);
}

function initTrackerToBeCreatedWithSelectedTracker(state: State, selected_tracker: Tracker): void {
    const name = selected_tracker.name;
    const color = selected_tracker.tlp_color;
    const shortname = getSlugifiedShortname(name);

    state.tracker_to_be_created = {
        name,
        shortname,
        color,
    };
}

export function reinitTrackerToBeCreatedData(state: State): void {
    state.tracker_to_be_created = {
        name: "",
        shortname: "",
        color: state.default_tracker_color,
    };
}

export async function setTrackerToBeCreatedFromXml(state: State): Promise<void> {
    state.is_parsing_a_xml_file = true;
    state.has_xml_file_error = false;

    if (!state.selected_xml_file_input || !state.selected_xml_file_input.files) {
        return;
    }

    const file = state.selected_xml_file_input.files.item(0);

    if (!file) {
        return;
    }

    await extractNameAndShortnameFromXmlFile(file)
        .then((xml_data: TrackerToBeCreatedMandatoryData) => {
            state.is_a_xml_file_selected = true;
            state.is_parsing_a_xml_file = false;
            state.tracker_to_be_created = xml_data;
        })
        .catch(() => {
            state.has_xml_file_error = true;
            state.is_parsing_a_xml_file = false;
        });
}

export function setTrackerName(state: State, name: string): void {
    state.tracker_to_be_created.name = name;

    if (state.is_in_slugify_mode) {
        setTrackerShortName(state, getSlugifiedShortname(name));
    }
}

export function setTrackerShortName(state: State, shortname: string): void {
    state.tracker_to_be_created.shortname = shortname;
}

export function setCreationFormHasBeenSubmitted(state: State): void {
    state.has_form_been_submitted = true;
}

export function cancelCreationFormSubmition(state: State): void {
    state.has_form_been_submitted = false;
}

export function setSelectedTrackerXmlFileInput(state: State, input: HTMLInputElement): void {
    state.selected_xml_file_input = input;
}

export function setSelectedProject(state: State, project: ProjectWithTrackers): void {
    state.selected_project = project;
}

export function setIsXmlAFileSelected(state: State): void {
    state.is_a_xml_file_selected = true;
}

export function setSlugifyShortnameMode(state: State, is_active: boolean): void {
    state.is_in_slugify_mode = is_active;
}

export function setJiraCredentials(state: State, credentials: Credentials): void {
    state.from_jira_data.credentials = credentials;
}

export function setProjectList(state: State, project_list: ProjectList[]): void {
    state.from_jira_data.project_list = project_list;
}

export function setTrackerList(state: State, tracker_list: TrackerList[]): void {
    state.from_jira_data.tracker_list = tracker_list;
}

export function setProject(state: State, project: ProjectList): void {
    state.from_jira_data.project = project;
}

export function setTracker(state: State, tracker: TrackerList): void {
    state.from_jira_data.tracker = tracker;
}
