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
import ProjectInformationInputPrivacySwitch from "./Input/ProjectInformationInputPrivacySwitch.vue";
import ProjectName from "./Input/ProjectName.vue";
import { State } from "../../store/type";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { Store } from "vuex-mock-store";

describe("ProjectInformation", () => {
    let factory: Wrapper<ProjectInformation>, state: State, store: Store;
    beforeEach(async () => {
        state = {
            selected_template: null,
            tuleap_templates: [],
            error: null,
            is_creating_project: false
        };

        const getters = {
            has_error: false,
            is_template_selected: false
        };

        const store_options = {
            state,
            getters
        };

        store = createStoreMock(store_options);

        factory = shallowMount(ProjectInformation, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store }
        });
    });
    it("Spawns the ProjectInformation component", () => {
        const wrapper = factory;

        wrapper.vm.$store.getters.has_error = false;

        expect(wrapper.contains(ProjectInformationSvg)).toBe(true);
        expect(wrapper.contains(UnderConstructionInformation)).toBe(true);
        expect(wrapper.contains(ProjectInformationFooter)).toBe(true);
        expect(wrapper.contains(ProjectInformationInputPrivacySwitch)).toBe(true);
        expect(wrapper.contains(ProjectName)).toBe(true);

        expect(wrapper.contains("[data-test=project-creation-failed]")).toBe(false);
    });

    it("Displays error message", () => {
        const wrapper = factory;

        wrapper.vm.$store.getters.has_error = true;

        expect(wrapper.contains(ProjectInformationSvg)).toBe(true);
        expect(wrapper.contains(UnderConstructionInformation)).toBe(true);
        expect(wrapper.contains(ProjectInformationFooter)).toBe(true);
        expect(wrapper.contains(ProjectInformationInputPrivacySwitch)).toBe(true);
        expect(wrapper.contains(ProjectName)).toBe(true);

        expect(wrapper.contains("[data-test=project-creation-failed]")).toBe(true);
    });
});
