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

describe(`ChangeFieldConfirmationModal`, () => {
    async function createWrapper(props) {
        return shallowMount(ChangeFieldConfirmationModal, {
            localVue: await createLocalVueForTests(),
            propsData: props,
        });
    }

    describe(`when an operation is running`, () => {
        let wrapper;
        beforeEach(async () => {
            wrapper = await createWrapper({
                is_operation_running: true,
            });
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
});
