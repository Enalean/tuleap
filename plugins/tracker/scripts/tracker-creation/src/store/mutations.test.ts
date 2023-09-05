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

import * as mutations from "./mutations";
import { setActiveOption } from "./mutations";
import type { ProjectTemplate, State, Tracker } from "./type";
import { NONE_YET, TRACKER_EMPTY, TRACKER_TEMPLATE } from "./type";

describe("mutations", () => {
    describe("setActiveOption", () => {
        it("stores the active option for empty", () => {
            const state: State = { active_option: NONE_YET } as State;
            setActiveOption(state, TRACKER_EMPTY);
            expect(state.active_option).toBe(TRACKER_EMPTY);
        });

        it("stores the active option for default template", () => {
            const tracker: Tracker = {
                id: "default-bug",
                name: "Bug",
                description: "Bug",
                tlp_color: "fiesta-red",
            };
            const state: State = {
                active_option: NONE_YET,
                project_templates: [] as ProjectTemplate[],
                default_templates: [tracker],
            } as State;
            setActiveOption(state, "default-bug");
            expect(state.active_option).toBe("default-bug");
            expect(state.selected_tracker_template).toBe(tracker);
        });

        it("resets the selected tracker when we switch active option", () => {
            const state: State = {
                active_option: "default-bug",
                project_templates: [] as ProjectTemplate[],
                selected_tracker_template: {
                    id: "default-bug",
                    name: "Bug",
                    description: "Bug",
                    tlp_color: "fiesta-red",
                },
            } as State;
            setActiveOption(state, TRACKER_TEMPLATE);
            expect(state.active_option).toBe(TRACKER_TEMPLATE);
            expect(state.selected_tracker_template).toBeNull();
        });
    });

    describe("setSelectedTrackerTemplate", () => {
        let state: State;

        beforeEach(() => {
            state = {
                default_templates: [{ id: "default-bug", name: "Bugs" }],
                project_templates: [
                    {
                        project_name: "Scrum template",
                        tracker_list: [
                            { id: "11", name: "Bugs" },
                            { id: "12", name: "Releases" },
                            { id: "13", name: "Requests" },
                        ],
                    },
                    {
                        project_name: "Default template",
                        tracker_list: [{ id: "14", name: "Activities" }],
                    },
                ],
                selected_tracker_template: null,
            } as State;
        });

        it("Given a tracker id, it finds the tracker in the state, then it stores it", () => {
            mutations.setSelectedTrackerTemplate(state, "13");

            expect(state.selected_tracker_template).toEqual({ id: "13", name: "Requests" });
        });

        it("Given a tracker id, it finds the tracker in the default trackers, then it stores it", () => {
            mutations.setSelectedTrackerTemplate(state, "default-bug");

            expect(state.selected_tracker_template).toEqual({ id: "default-bug", name: "Bugs" });
        });

        it("throws an error when the tracker has not been found", () => {
            expect(() => mutations.setSelectedTrackerTemplate(state, "15")).toThrowError(
                "not found",
            );
        });
    });

    describe("initTrackerNameWithTheSelectedTemplateName", () => {
        it("does nothing if no tracker template is selected", () => {
            const state: State = {
                selected_tracker_template: null,
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                    color: "",
                },
            } as State;

            mutations.initTrackerNameWithTheSelectedTemplateName(state);

            expect(state.tracker_to_be_created).toEqual({
                name: "",
                shortname: "",
                color: "",
            });
        });

        it("Sets the tracker name, shortname (slugified) and color", () => {
            const state: State = {
                selected_tracker_template: {
                    name: "Bug tracker",
                    tlp_color: "peggy-pink",
                },
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                    color: "",
                },
            } as State;

            mutations.initTrackerNameWithTheSelectedTemplateName(state);

            expect(state.tracker_to_be_created).toEqual({
                name: "Bug tracker",
                shortname: "bug_tracker",
                color: "peggy-pink",
            });
        });
    });

    describe("initTrackerNameWithTheSelectedProjectTrackerTemplateName", () => {
        it("does nothing if no tracker template is selected", () => {
            const state: State = {
                selected_project_tracker_template: null,
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                    color: "",
                },
            } as State;

            mutations.initTrackerNameWithTheSelectedProjectTrackerTemplateName(state);

            expect(state.tracker_to_be_created).toEqual({
                name: "",
                shortname: "",
                color: "",
            });
        });

        it("Sets the tracker name, shortname (slugified) and color", () => {
            const state: State = {
                selected_project_tracker_template: {
                    name: "Bug tracker",
                    tlp_color: "peggy-pink",
                },
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                    color: "",
                },
            } as State;

            mutations.initTrackerNameWithTheSelectedProjectTrackerTemplateName(state);

            expect(state.tracker_to_be_created).toEqual({
                name: "Bug tracker",
                shortname: "bug_tracker",
                color: "peggy-pink",
            });
        });
    });

    describe("setTrackerName", () => {
        it("Sets the tracker name", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                },
            } as State;

            mutations.setTrackerName(state, "Kanban in the trees");

            expect(state.tracker_to_be_created).toEqual({
                name: "Kanban in the trees",
                shortname: "",
            });
        });

        it("When the slugify mode is active, it also sets the tracker shortname", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "",
                    shortname: "",
                },
                is_in_slugify_mode: true,
            } as State;

            mutations.setTrackerName(state, "Kanban in the trees");

            expect(state.tracker_to_be_created).toEqual({
                name: "Kanban in the trees",
                shortname: "kanban_in_the_trees",
            });
        });
    });
});
