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

import StepOne from "./StepOne.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ProjectTemplate, State, Tracker } from "../../../store/type";
import { createTrackerCreationLocalVue } from "../../../helpers/local-vue-for-tests";

describe("StepOne", () => {
    it("resets the slugify mode when it is mounted", async () => {
        const wrapper: Wrapper<StepOne> = shallowMount(StepOne, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        default_templates: [] as Tracker[],
                        project_templates: [] as ProjectTemplate[],
                    } as State,
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSlugifyShortnameMode", true);
    });

    it(`displays the company name if the platform name is not Tuleap`, async () => {
        const wrapper = shallowMount(StepOne, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        default_templates: [] as Tracker[],
                        project_templates: [{} as ProjectTemplate],
                        company_name: "Nichya company",
                    } as State,
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });

        expect(wrapper.get("[data-test=platform-template-name]").text()).toBe(
            "Nichya company templates",
        );
    });

    it(`displays 'Custom templates' if the platform name is Tuleap`, async () => {
        const wrapper = shallowMount(StepOne, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        default_templates: [] as Tracker[],
                        project_templates: [{} as ProjectTemplate],
                        company_name: "Tuleap",
                    } as State,
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });

        expect(wrapper.get("[data-test=platform-template-name]").text()).toBe("Custom templates");
    });

    it(`Does not display custom template block if there is no project_templates`, async () => {
        const wrapper = shallowMount(StepOne, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        default_templates: [] as Tracker[],
                        project_templates: [] as ProjectTemplate[],
                        company_name: "Tuleap",
                    } as State,
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });

        expect(wrapper.find("[data-test=platform-template-name]").exists()).toBe(false);
    });
});
