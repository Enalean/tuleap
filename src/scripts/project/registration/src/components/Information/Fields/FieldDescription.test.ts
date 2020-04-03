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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import FieldDescription from "./FieldDescription.vue";
import { State } from "../../../store/type";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";

describe("FieldDescription -", () => {
    let factory: Wrapper<FieldDescription>;
    beforeEach(async () => {
        const state: State = {
            is_description_required: false,
        } as State;

        const getters = {
            has_error: false,
            is_template_selected: false,
        };

        const store_options = {
            state,
            getters,
        };

        const store = createStoreMock(store_options);

        factory = shallowMount(FieldDescription, {
            localVue: await createProjectRegistrationLocalVue(),
            mocks: { $store: store },
        });
    });
    it("add correct attribute when description is required", async () => {
        const wrapper = factory;
        wrapper.vm.$store.state.is_description_required = true;

        const description = wrapper.get("[data-test=project-description]")
            .element as HTMLTextAreaElement;
        await wrapper.vm.$nextTick();

        expect(description.required).toBe(true);
    });

    it("add correct attribute when description is NOT requried", async () => {
        const wrapper = factory;
        wrapper.vm.$store.state.is_description_required = false;

        const description = wrapper.get("[data-test=project-description]")
            .element as HTMLTextAreaElement;
        await wrapper.vm.$nextTick();

        expect(description.required).toBe(false);
    });
});
