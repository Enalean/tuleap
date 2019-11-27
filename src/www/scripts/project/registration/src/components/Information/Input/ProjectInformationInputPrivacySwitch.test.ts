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

describe("ProjectInformationInputPrivacySwitch", () => {
    let factory: Wrapper<ProjectInformationInputPrivacySwitch>;
    beforeEach(async () => {
        factory = shallowMount(ProjectInformationInputPrivacySwitch, {
            localVue: await createProjectRegistrationLocalVue()
        });
    });
    it("Spawns the ProjectInformationInputPrivacySwitch component", () => {
        expect(factory.contains(ProjectInformationInputPrivacySwitch)).toBe(true);
    });
    it("changes the tooltip text when the project privacy is private", () => {
        const wrapper = factory;
        const tooltip: HTMLSpanElement = wrapper.find(
            "[data-test=project-information-input-privacy-tooltip]"
        ).element;

        expect(tooltip.getAttribute("data-tlp-tooltip")).toBe(
            "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items."
        );

        wrapper.find("[data-test=project-information-input-privacy-switch]").trigger("click");

        expect(tooltip.getAttribute("data-tlp-tooltip")).toBe(
            "Project privacy set to private. Only project members can access its content. Restricted users are not allowed in this project."
        );
    });
});
