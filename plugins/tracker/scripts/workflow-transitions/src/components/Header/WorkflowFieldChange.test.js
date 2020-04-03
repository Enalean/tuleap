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
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../support/local-vue.js";
import * as tlp from "tlp";
import WorkflowFieldChange from "./WorkflowFieldChange.vue";

jest.mock("tlp");

describe(`WorkflowFieldChange`, () => {
    let wrapper, store;

    function createWrapper() {
        store = createStoreMock({
            state: {
                is_operation_running: false,
            },
            getters: {
                workflow_field_label: "Status",
                current_tracker_id: 145,
            },
        });
        wrapper = shallowMount(WorkflowFieldChange, {
            localVue,
            mocks: { $store: store },
        });
    }

    it(`when mounted(), it will create a TLP modal`, () => {
        createWrapper();
        expect(tlp.modal).toHaveBeenCalled();
    });

    it(`when I click the "Change or remove" button, it will open a confirmation modal`, () => {
        const modal = {
            show: jest.fn(),
        };
        jest.spyOn(tlp, "modal").mockReturnValue(modal);
        createWrapper();

        const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
        change_remove_button.trigger("click");

        expect(modal.show).toHaveBeenCalled();
    });

    describe(`when an operation is running`, () => {
        it(`will disable the "Change or remove" button`, async () => {
            createWrapper();
            store.state.is_operation_running = true;
            await wrapper.vm.$nextTick();
            const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
            expect(change_remove_button.attributes("disabled")).toBeTruthy();
        });
    });
});
