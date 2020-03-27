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

import * as getters from "./getters";
import {
    CreationOptions,
    ProjectTemplate,
    State,
    Tracker,
    TrackerToBeCreatedMandatoryData,
} from "./type";

describe("getters", () => {
    describe("is_ready_for_step_2", () => {
        it("Is not ready if no option is selected", () => {
            const state: State = {
                active_option: CreationOptions.NONE_YET,
                selected_tracker_template: null,
                is_a_xml_file_selected: false,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is not ready if no tracker template is selected", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_TEMPLATE,
                selected_tracker_template: null,
                is_a_xml_file_selected: false,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is ready if TRACKER_TEMPLATE option is selected along with a tracker", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_TEMPLATE,
                selected_tracker_template: {
                    id: "101",
                    name: "Bugs",
                    tlp_color: "peggy-pink",
                } as Tracker,
                is_a_xml_file_selected: false,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(true);
        });

        it("Is not ready if TRACKER_XML_FILE option is selected along with an invalid xml file", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_XML_FILE,
                selected_tracker_template: null,
                is_a_xml_file_selected: true,
                has_xml_file_error: true,
                is_parsing_a_xml_file: false,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is not ready if TRACKER_XML_FILE option is selected but the selected XML file is being parsed", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_XML_FILE,
                selected_tracker_template: null,
                is_a_xml_file_selected: true,
                has_xml_file_error: true,
                is_parsing_a_xml_file: true,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is ready if TRACKER_XML_FILE option is selected along with a xml file", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_XML_FILE,
                selected_tracker_template: null,
                is_a_xml_file_selected: true,
                has_xml_file_error: false,
                is_parsing_a_xml_file: false,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(true);
        });

        it("Is ready if TRACKER_EMPTY option is selected", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_EMPTY,
                selected_tracker_template: null,
                is_a_xml_file_selected: true,
                has_xml_file_error: false,
                is_parsing_a_xml_file: false,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(true);
        });

        it("Is not ready if TRACKER_ANOTHER_PROJECT option is selected with no tracker template", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_ANOTHER_PROJECT,
                selected_tracker_template: null,
                is_a_xml_file_selected: true,
                has_xml_file_error: false,
                is_parsing_a_xml_file: false,
                selected_project_tracker_template: null,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is ready if TRACKER_ANOTHER_PROJECT option is selected along with a tracker template", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_ANOTHER_PROJECT,
                selected_tracker_template: null,
                is_a_xml_file_selected: true,
                has_xml_file_error: false,
                is_parsing_a_xml_file: false,
                selected_project_tracker_template: {} as Tracker,
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(true);
        });
    });

    describe("is_ready_to_submit", () => {
        it("Is not ready if the tracker has no name", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                },
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is not ready if the tracker has no shortname", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "Bugz",
                    shortname: "",
                },
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is not ready if the tracker name is already used", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "Bugz",
                    shortname: "",
                },
                existing_trackers: {
                    names: ["Bugz"],
                    shortnames: ["bugz"],
                },
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is not ready if the tracker shortname is already used", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "EPICS",
                    shortname: "epico",
                },
                existing_trackers: {
                    names: ["Bugz"],
                    shortnames: ["epico"],
                },
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is not ready if the tracker shortname does not respect the expected format", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "EPICS",
                    shortname: "I dont care the expected format",
                },
                existing_trackers: {
                    names: ["Bugz"],
                    shortnames: ["epico"],
                },
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is ready otherwise", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "EPICS",
                    shortname: "epico",
                },
                existing_trackers: {
                    names: ["Bugz"],
                    shortnames: ["bugz"],
                },
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(true);
        });
    });

    describe("can_display_slugify_mode", () => {
        it("can't be displayed when the user has toggled the shortname input", () => {
            const state = {
                is_in_slugify_mode: false,
                active_option: CreationOptions.TRACKER_TEMPLATE,
            } as State;

            expect(getters.can_display_slugify_mode(state)).toBe(false);
        });

        it("can't be displayed when the user has selected the XML import option (shortname extracted from XML)", () => {
            const state = {
                is_in_slugify_mode: true,
                active_option: CreationOptions.TRACKER_XML_FILE,
            } as State;

            expect(getters.can_display_slugify_mode(state)).toBe(false);
        });

        it("can be displayed otherwise", () => {
            const state = {
                is_in_slugify_mode: true,
                active_option: CreationOptions.TRACKER_TEMPLATE,
            } as State;

            expect(getters.can_display_slugify_mode(state)).toBe(true);
        });
    });

    describe("is_shortname_valid", () => {
        function getStateWithShortname(shortname: string): State {
            return {
                tracker_to_be_created: {
                    shortname,
                } as TrackerToBeCreatedMandatoryData,
            } as State;
        }

        it("returns false when the shortname contains forbidden characters", () => {
            expect(getters.is_shortname_valid(getStateWithShortname(" "))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("+"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("."))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("~"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("("))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname(")"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("!"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname(":"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("@"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname('"'))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("'"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("*"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("©"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("®"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("-"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname("<"))).toBe(false);
            expect(getters.is_shortname_valid(getStateWithShortname(">"))).toBe(false);
        });

        it("returns true when the shortname is well formatted", () => {
            expect(getters.is_shortname_valid(getStateWithShortname("yo_lo"))).toBe(true);
            expect(getters.is_shortname_valid(getStateWithShortname("122_yo_lo"))).toBe(true);
            expect(getters.is_shortname_valid(getStateWithShortname("_yo_lo_"))).toBe(true);
            expect(getters.is_shortname_valid(getStateWithShortname("tracker123"))).toBe(true);
        });
    });

    describe("is_[shortname][name]_already_used", () => {
        function getState(tracker_to_be_created: TrackerToBeCreatedMandatoryData): State {
            return {
                existing_trackers: {
                    names: ["bugs", "user stories", "releases", "epics", "activities"],
                    shortnames: ["bug", "story", "release", "epic", "activity"],
                },
                tracker_to_be_created,
            } as State;
        }

        it("Returns true they already exist", () => {
            const state = getState({
                name: "Epics",
                shortname: "epic",
                color: "peggy-pink",
            });

            expect(getters.is_name_already_used(state)).toBe(true);
            expect(getters.is_shortname_already_used(state)).toBe(true);
        });

        it("Returns false otherwise", () => {
            const state = getState({
                name: "Requirements",
                shortname: "requirement",
                color: "peggy-pink",
            });

            expect(getters.is_name_already_used(state)).toBe(false);
            expect(getters.is_shortname_already_used(state)).toBe(false);
        });
    });

    describe("project_of_selected_tracker_template", () => {
        it("returns null when no tracker template is selected", () => {
            const state: State = {
                selected_tracker_template: null,
            } as State;

            expect(getters.project_of_selected_tracker_template(state)).toBeNull();
        });

        it("returns null when the project has not been found", () => {
            const state: State = {
                selected_tracker_template: {
                    id: "5",
                    name: "Bugs",
                },
                project_templates: [] as ProjectTemplate[],
            } as State;

            expect(getters.project_of_selected_tracker_template(state)).toBeNull();
        });

        it("returns the project owning the selected template", () => {
            const state: State = {
                selected_tracker_template: {
                    id: "5",
                    name: "Bugs",
                },
                project_templates: [
                    {
                        project_name: "Project 1",
                        tracker_list: [{ id: "1" } as Tracker],
                    },
                    {
                        project_name: "Project X",
                        tracker_list: [{ id: "5" } as Tracker],
                    },
                ] as ProjectTemplate[],
            } as State;

            expect(getters.project_of_selected_tracker_template(state)).toEqual({
                project_name: "Project X",
                tracker_list: [{ id: "5" }],
            });
        });
    });
});
