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
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import ProjectInformation from "./ProjectInformation.vue";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import UnderConstructionInformation from "../UnderConstructionInformation.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";

describe("ProjectInformation", () => {
    let factory: Wrapper<ProjectInformation>;
    beforeEach(async () => {
        factory = shallowMount(ProjectInformation, {
            localVue: await createProjectRegistrationLocalVue()
        });
    });
    it("Spawns the ProjectInformation component", () => {
        const wrapper = factory;

        expect(wrapper.contains(ProjectInformationSvg)).toBe(true);
        expect(wrapper.contains(UnderConstructionInformation)).toBe(true);
        expect(wrapper.contains(ProjectInformationFooter)).toBe(true);
        expect(wrapper.contains("[data-test=register-new-project-information-form]")).toBe(true);
    });
});
