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
import { StoreOptions } from "../../../store/type";

describe("ProjectInformationInputPrivacySwitch", () => {
    async function getProjectInformationPrivacySwitchInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ProjectInformationInputPrivacySwitch>> {
        const store = createStoreMock(store_options);
        return shallowMount(ProjectInformationInputPrivacySwitch, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store }
        });
    }

    it("Spawns the ProjectInformationInputPrivacySwitch component", async () => {
        const state = {
            selected_template: null,
            tuleap_templates: [],
            project_default_visibility: "public",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            are_anonymous_allowed: false
        };

        const store_options = { state };

        const factory = await getProjectInformationPrivacySwitchInstance(store_options);
        expect(factory.contains(ProjectInformationInputPrivacySwitch)).toBe(true);
    });
    it("changes the tooltip text when the project privacy is private", async () => {
        const state = {
            selected_template: null,
            tuleap_templates: [],
            project_default_visibility: "public",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            are_anonymous_allowed: false
        };

        const store_options = { state };

        const wrapper = await getProjectInformationPrivacySwitchInstance(store_options);

        const tooltip: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-tooltip]"
        ).element;

        expect(tooltip.getAttribute("data-tlp-tooltip")).toBe(
            "Project privacy set to public. By default, its content is available to all authenticated. Please note that more restrictive permissions might exist on some items."
        );

        wrapper.find("[data-test=project-information-input-privacy-switch]").trigger("click");

        expect(tooltip.getAttribute("data-tlp-tooltip")).toBe(
            "Project privacy set to private. Only project members can access its content."
        );
    });
    it("displays the right message when the plateform does not allow anonymous users", async () => {
        const state = {
            selected_template: null,
            tuleap_templates: [],
            project_default_visibility: "public",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            are_anonymous_allowed: false
        };

        const store_options = { state };

        const wrapper = await getProjectInformationPrivacySwitchInstance(store_options);

        const tooltip: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-tooltip]"
        ).element;

        expect(tooltip.getAttribute("data-tlp-tooltip")).toBe(
            "Project privacy set to public. By default, its content is available to all authenticated. Please note that more restrictive permissions might exist on some items."
        );
    });

    it("displays the right message when the platform allows anonymous users", async () => {
        const state = {
            selected_template: null,
            tuleap_templates: [],
            project_default_visibility: "public",
            error: null,
            is_creating_project: false,
            is_project_approval_required: false,
            are_anonymous_allowed: true
        };

        const store_options = { state };

        const wrapper = await getProjectInformationPrivacySwitchInstance(store_options);

        const tooltip: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-tooltip]"
        ).element;

        expect(tooltip.getAttribute("data-tlp-tooltip")).toBe(
            "Project privacy set to public. By default, its content is available to everyone (authenticated or not). Please note that more restrictive permissions might exist on some items."
        );
    });
});
