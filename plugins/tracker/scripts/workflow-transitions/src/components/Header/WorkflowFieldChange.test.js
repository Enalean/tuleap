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
import * as tlp_modal from "@tuleap/tlp-modal";
import WorkflowFieldChange from "./WorkflowFieldChange.vue";

const noop = () => {
    //Do nothing
};

describe(`WorkflowFieldChange`, () => {
    let store;

    beforeEach(() => {
        jest.spyOn(tlp_modal, "createModal").mockReturnValue({
            show: noop,
        });
    });

    async function createWrapper() {
        store = createStoreMock({
            state: {
                is_operation_running: false,
            },
            getters: {
                workflow_field_label: "Status",
                current_tracker_id: 145,
            },
        });
        return shallowMount(WorkflowFieldChange, {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        });
    }

    it(`when mounted(), it will create a TLP modal`, async () => {
        const createModal = jest.spyOn(tlp_modal, "createModal");
        await createWrapper();
        expect(createModal).toHaveBeenCalled();
    });

    it(`when I click the "Change or remove" button, it will open a confirmation modal`, async () => {
        const modal = {
            show: jest.fn(),
        };
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(modal);
        const wrapper = await createWrapper();

        const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
        change_remove_button.trigger("click");

        expect(modal.show).toHaveBeenCalled();
    });

    describe(`when an operation is running`, () => {
        it(`will disable the "Change or remove" button`, async () => {
            const wrapper = await createWrapper();
            store.state.is_operation_running = true;
            await wrapper.vm.$nextTick();
            const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
            expect(change_remove_button.attributes("disabled")).toBeTruthy();
        });
    });
});
