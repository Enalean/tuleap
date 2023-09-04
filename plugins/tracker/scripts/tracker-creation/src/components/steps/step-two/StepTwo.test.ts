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

import type { State } from "../../../store/type";
import type { Wrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import StepTwo from "./StepTwo.vue";
import { createTrackerCreationLocalVue } from "../../../helpers/local-vue-for-tests";

describe("StepTwo", () => {
    async function getWrapper(
        state: State = {} as State,
        is_a_duplication = false,
        is_a_xml_import = false,
        is_created_from_empty = false,
        is_a_duplication_of_a_tracker_from_another_project = false,
        is_created_from_default_template = false,
        is_created_from_jira = false,
    ): Promise<Wrapper<StepTwo>> {
        return mount(StepTwo, {
            mocks: {
                $store: createStoreMock({
                    state,
                    getters: {
                        is_a_duplication,
                        is_a_xml_import,
                        is_created_from_empty,
                        is_a_duplication_of_a_tracker_from_another_project,
                        is_ready_to_submit: true,
                        is_created_from_default_template,
                        is_created_from_jira,
                    },
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
            stubs: {
                "field-chosen-template": true,
                "field-csrf-token": true,
                "field-name": true,
                "field-shortname": true,
                "field-description": true,
                "field-tracker-template-id": true,
                "router-link": true,
                "field-tracker-empty": true,
                "field-tracker-color": true,
                "field-from-jira": true,
            },
        });
    }

    describe("Tracker duplication", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            wrapper = await getWrapper({} as State, true);
        });

        it("auto-fills the tracker name with the name of the selected tracker", () => {
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "initTrackerNameWithTheSelectedTemplateName",
            );
        });

        it("renders a field-tracker-template-id", () => {
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(true);
        });

        it("Sets the right encoding type for the form", () => {
            expect(wrapper.get("#tracker-creation-form").attributes("enctype")).toBe(
                "application/x-www-form-urlencoded",
            );
        });
    });

    describe("From default template", () => {
        it("auto-fills the tracker name with the name of the selected tracker", async () => {
            const wrapper = await getWrapper({} as State, false, false, false, false, true);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "initTrackerNameWithTheSelectedTemplateName",
            );
        });

        it("renders a field-tracker-template-id", async () => {
            const wrapper = await getWrapper({} as State, false, false, false, false, true);
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(true);
        });
    });

    describe("Tracker from another project duplication", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            wrapper = await getWrapper({} as State, false, false, false, true);
        });

        it("auto-fills the tracker name with the name of the selected tracker", () => {
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "initTrackerNameWithTheSelectedProjectTrackerTemplateName",
            );
        });

        it("renders a field-tracker-template-id", () => {
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(true);
        });

        it("Sets the right encoding type for the form", () => {
            expect(wrapper.get("#tracker-creation-form").attributes("enctype")).toBe(
                "application/x-www-form-urlencoded",
            );
        });
    });

    describe("XML import", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            const file_input = document.implementation.createHTMLDocument().createElement("input");
            file_input.setAttribute("data-test", "injected-file-input");

            const state = {
                tracker_to_be_created: {
                    name: "Kanban in the trees",
                    shortname: "kanban_in_the_trees",
                },
                selected_xml_file_input: file_input,
            } as State;

            wrapper = await getWrapper(state, false, true);
        });

        it("appends the file input filled during step 1 to the form", () => {
            expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(false);
        });

        it("Sets the right encoding type for the form", () => {
            expect(wrapper.get("#tracker-creation-form").attributes("enctype")).toBe(
                "multipart/form-data",
            );
        });
    });

    describe("Create from empty", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            const file_input = document.implementation.createHTMLDocument().createElement("input");
            file_input.setAttribute("data-test", "injected-file-input");

            wrapper = await getWrapper(
                {
                    tracker_to_be_created: {
                        name: "Kanban in the trees",
                        shortname: "kanban_in_the_trees",
                    },
                    selected_xml_file_input: file_input,
                } as State,
                false,
                false,
                true,
            );
        });

        it("appends the hidden input", () => {
            expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(false);
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(false);
            expect(wrapper.find("field-tracker-empty-stub").exists()).toBe(true);
        });

        it("Sets the right encoding type for the form", () => {
            expect(wrapper.get("#tracker-creation-form").attributes("enctype")).toBe(
                "application/x-www-form-urlencoded",
            );
        });
    });

    describe("Create from jira", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            wrapper = await getWrapper({} as State, false, false, false, false, false, true);
        });

        it("appends the hidden input", () => {
            expect(wrapper.find("field-from-jira-stub").exists()).toBe(true);
        });
    });

    describe("Remove error", () => {
        beforeEach(() => {
            const error = document.implementation.createHTMLDocument().createElement("div");
            error.setAttribute("id", "feedback");
        });

        it("Global HTML no longer have a feedback error", async () => {
            await getWrapper({} as State, true);
            expect(document.getElementById("feedback")).toBeNull();
        });
    });
});
