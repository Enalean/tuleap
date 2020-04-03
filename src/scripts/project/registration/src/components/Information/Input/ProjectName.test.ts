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
import EventBus from "../../../helpers/event-bus";

describe("ProjectName", () => {
    let wrapper: Wrapper<ProjectName>,
        store: Store,
        component_options: ShallowMountOptions<ProjectName>;
    beforeEach(async () => {
        component_options = {
            data(): DefaultData<ProjectName> {
                return {
                    error: "",
                };
            },
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
        };

        store = createStoreMock({});

        wrapper = shallowMount(ProjectName, component_options);
    });

    it(`Should not yields again user, if he just started to type its new project short name, even if the minimal length is not reached`, () => {
        wrapper = shallowMount(ProjectName, component_options);
        wrapper.vm.$data.written_chars = 0;
        wrapper.get("[data-test=new-project-name]").setValue("t");
        expect(wrapper.vm.$data.has_error).toBe(false);

        expect(wrapper.contains("[data-test=project-name-is-invalid]")).toBe(false);
    });

    it(`Should yields error when user has write more than 3 character ans when minimal project length is not reached`, async () => {
        wrapper = shallowMount(ProjectName, component_options);
        wrapper.vm.$data.written_chars = 4;
        wrapper.get("[data-test=new-project-name]").setValue("t");
        await wrapper.vm.$nextTick();
        expect(wrapper.vm.$data.has_error).toBe(true);

        expect(wrapper.contains("[data-test=project-name-is-invalid]")).toBe(true);
    });

    it(`Emit a named event`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        wrapper = shallowMount(ProjectName, component_options);
        wrapper.get("[data-test=new-project-name]").setValue("test");

        expect(wrapper.contains("[data-test=project-project-name-is-invalid]")).toBe(false);

        expect(event_bus_emit).toHaveBeenCalledWith("slugify-project-name", "test");
    });
});
