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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ProjectName from "./ProjectName.vue";
import type { DefaultData } from "vue/types/options";
import EventBus from "../../../helpers/event-bus";

describe("ProjectName", () => {
    async function createWrapper(): Promise<Wrapper<Vue, Element>> {
        const store = createStoreMock({});
        const component_options = {
            data(): DefaultData<Element> {
                return {
                    error: "",
                };
            },
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
        };

        return shallowMount(ProjectName, component_options);
    }

    it(`Should not yields again user, if he just started to type its new project short name, even if the minimal length is not reached`, async () => {
        const wrapper = await createWrapper();
        wrapper.get("[data-test=new-project-name]").setValue("t");

        expect(wrapper.find("[data-test=project-name-is-invalid]").exists()).toBe(false);
    });

    it(`Emit a named event`, async () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const wrapper = await createWrapper();
        wrapper.get("[data-test=new-project-name]").setValue("test");

        expect(wrapper.find("[data-test=project-project-name-is-invalid]").exists()).toBe(false);

        expect(event_bus_emit).toHaveBeenCalledWith("slugify-project-name", "test");
    });
});
