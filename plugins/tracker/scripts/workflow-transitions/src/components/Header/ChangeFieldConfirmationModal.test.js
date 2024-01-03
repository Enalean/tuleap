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
import { createLocalVueForTests } from "../../support/local-vue.js";
import ChangeFieldConfirmationModal from "./ChangeFieldConfirmationModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe(`ChangeFieldConfirmationModal`, () => {
    let create_modal_spy;
    beforeEach(() => {
        const fake_modal = {
            addEventListener: () => {},
            show: jest.fn(),
            hide: jest.fn(),
        };
        create_modal_spy = jest.spyOn(tlp_modal, "createModal").mockReturnValue(fake_modal);
    });

    async function createWrapper(is_operation_running) {
        const store = createStoreMock({
            state: {
                is_operation_running: is_operation_running,
            },
            getters: {
                workflow_field_label: "Status",
                current_tracker_id: 145,
            },
        });

        return shallowMount(ChangeFieldConfirmationModal, {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        });
    }

    describe(`when an operation is running`, () => {
        let wrapper;
        beforeEach(async () => {
            wrapper = await createWrapper(true);
        });

        it(`will disable the "Confirm" button`, () => {
            const confirm_button = wrapper.get("[data-test=confirm-button]");
            expect(confirm_button.attributes("disabled")).toBeTruthy();
        });

        it(`will show a spinner icon on the "Confirm" button`, () => {
            const spinner_icon = wrapper.find("[data-test=confirm-button-spinner]");
            expect(spinner_icon.exists()).toBe(true);
        });
    });

    it(`when mounted(), it will create a TLP modal`, async () => {
        await createWrapper(false);
        expect(create_modal_spy).toHaveBeenCalled();
    });
});
