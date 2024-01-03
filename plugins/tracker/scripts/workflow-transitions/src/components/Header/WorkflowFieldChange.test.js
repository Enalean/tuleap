/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createLocalVueForTests } from "../../support/local-vue.js";
import WorkflowFieldChange from "./WorkflowFieldChange.vue";

describe(`WorkflowFieldChange`, () => {
    async function createWrapper(is_operation_running) {
        const store = createStoreMock({
            state: {
                is_operation_running,
            },
            getters: {
                workflow_field_label: "Status",
            },
        });
        return shallowMount(WorkflowFieldChange, {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        });
    }

    describe(`when an operation is running`, () => {
        it(`will disable the "Change or remove" button`, async () => {
            const wrapper = await createWrapper(true);
            const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
            expect(change_remove_button.attributes("disabled")).toBe("disabled");
        });
    });

    it(`when I click the "Change or remove" button, it will open a confirmation modal`, async () => {
        const wrapper = await createWrapper(false);

        const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
        change_remove_button.trigger("click");

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=change-field-confirmation-modal]").exists()).toBe(true);
    });
});
