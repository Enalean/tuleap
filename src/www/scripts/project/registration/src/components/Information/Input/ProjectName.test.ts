/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import { Store } from "vuex-mock-store";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import ProjectName from "./ProjectName.vue";
import { DefaultData } from "vue/types/options";

describe("ProjectName", () => {
    let wrapper: Wrapper<ProjectName>,
        store: Store,
        component_options: ShallowMountOptions<ProjectName>;
    beforeEach(async () => {
        component_options = {
            data(): DefaultData<ProjectName> {
                return {
                    slugified_project_name: "",
                    error: ""
                };
            },
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store }
        };

        store = createStoreMock({});

        wrapper = shallowMount(ProjectName, component_options);
    });

    it(`Display an error when shortname has less than 3 characters`, () => {
        wrapper.find("[data-test=new-project-name]").setValue("My");

        expect(wrapper.vm.$data.slugified_project_name).toBe("My");
        expect(wrapper.vm.$data.error.length).toBeGreaterThan(1);
    });

    it(`Display an error when shortname start by a numerical character`, () => {
        wrapper.find("[data-test=new-project-name]").setValue("0My project");

        expect(wrapper.vm.$data.slugified_project_name).toBe("0My-project");
        expect(wrapper.vm.$data.error.length).toBeGreaterThan(1);
    });

    it(`Display an error when shortname contains invalid characters`, () => {
        wrapper.find("[data-test=new-project-name]").setValue("******");

        expect(wrapper.vm.$data.slugified_project_name).toBe("******");
        expect(wrapper.vm.$data.error.length).toBeGreaterThan(1);
    });

    it(`Store and validate the project name`, () => {
        wrapper.find("[data-test=new-project-name]").setValue("My project name");
        expect(wrapper.vm.$data.slugified_project_name).toBe("My-project-name");
        expect(wrapper.vm.$data.error.length).toBe(0);
    });

    it(`Slugified project name handle correctly the accents`, () => {
        wrapper.find("[data-test=new-project-name]").setValue("Accentué ç è é ù ë");
        expect(wrapper.vm.$data.slugified_project_name).toBe("Accentue-c-e-e-u-e");
        expect(wrapper.vm.$data.error.length).toBe(0);
    });
});
