/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import ProjectInformationInputPrivacySwitch from "./ProjectInformationInputPrivacySwitch.vue";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import { State } from "../../../store/type";
import { TemplateData } from "../../../type";
import * as tlp from "tlp";
jest.mock("tlp");

describe("ProjectInformationInputPrivacySwitch", () => {
    let factory: Wrapper<ProjectInformationInputPrivacySwitch>;

    beforeEach(async () => {
        const state = {
            project_default_visibility: "public",
            tuleap_templates: [] as TemplateData[],
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            are_anonymous_allowed: false
        } as State;

        const store_options = { state };

        const store = createStoreMock(store_options);

        factory = shallowMount(ProjectInformationInputPrivacySwitch, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store }
        });

        jest.spyOn(tlp, "createPopover");
    });

    it("Spawns the ProjectInformationInputPrivacySwitch component", () => {
        const wrapper = factory;

        wrapper.vm.$store.state.selected_template = null;
        wrapper.vm.$store.state.tuleap_templates = [];
        wrapper.vm.$store.state.project_default_visibility = "public";
        wrapper.vm.$store.state.error = null;
        wrapper.vm.$store.state.is_creating_project = false;
        wrapper.vm.$store.state.is_project_approval_required = false;
        wrapper.vm.$store.state.are_anonymous_allowed = false;

        expect(wrapper).toMatchSnapshot();
    });
    it("changes the tooltip text when the project privacy is private", () => {
        const wrapper = factory;

        wrapper.vm.$store.state.selected_template = null;
        wrapper.vm.$store.state.tuleap_templates = [];
        wrapper.vm.$store.state.project_default_visibility = "public";
        wrapper.vm.$store.state.error = null;
        wrapper.vm.$store.state.is_creating_project = false;
        wrapper.vm.$store.state.is_project_approval_required = false;
        wrapper.vm.$store.state.are_anonymous_allowed = false;

        const popover_content: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-text]"
        ).element;

        expect(popover_content.innerHTML.trim()).toEqual(
            "Project privacy set to public. By default, its content is available to all authenticated. Please note that more restrictive permissions might exist on some items."
        );

        wrapper.find("[data-test=project-information-input-privacy-switch]").trigger("click");

        expect(popover_content.innerHTML.trim()).toEqual(
            "Project privacy set to private. Only project members can access its content."
        );
    });
    it("displays the right message when the platform does not allow anonymous users", () => {
        const wrapper = factory;

        wrapper.vm.$store.state.selected_template = null;
        wrapper.vm.$store.state.tuleap_templates = [];
        wrapper.vm.$store.state.project_default_visibility = "public";
        wrapper.vm.$store.state.error = null;
        wrapper.vm.$store.state.is_creating_project = false;
        wrapper.vm.$store.state.is_project_approval_required = false;
        wrapper.vm.$store.state.are_anonymous_allowed = false;

        const popover_content: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-text]"
        ).element;

        expect(popover_content.innerHTML.trim()).toEqual(
            "Project privacy set to public. By default, its content is available to all authenticated. Please note that more restrictive permissions might exist on some items."
        );
    });

    it("displays the right message when the platform allows anonymous users", () => {
        const wrapper = factory;

        wrapper.vm.$store.state.selected_template = null;
        wrapper.vm.$store.state.tuleap_templates = [];
        wrapper.vm.$store.state.project_default_visibility = "public";
        wrapper.vm.$store.state.error = null;
        wrapper.vm.$store.state.is_creating_project = false;
        wrapper.vm.$store.state.is_project_approval_required = false;
        wrapper.vm.$store.state.are_anonymous_allowed = true;

        const popover_content: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-text]"
        ).element;

        expect(popover_content.innerHTML.trim()).toEqual(
            "Project privacy set to public. By default, its content is available to everyone (authenticated or not). Please note that more restrictive permissions might exist on some items."
        );
    });
});
