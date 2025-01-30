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

import type { Vue } from "vue/types/vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import ErrorModal from "./ErrorModal.vue";
import { createProgramManagementLocalVue } from "../../helpers/local-vue-for-test";

describe("ErrorModal", () => {
    beforeEach(() => {
        const fake_modal = {
            show(): void {
                //Do nothing
            },
        } as Modal;
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(fake_modal);
    });

    async function createWrapper(error_message: string): Promise<Wrapper<Vue>> {
        return shallowMount(ErrorModal, {
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: { modal_error_message: error_message },
                }),
            },
        });
    }

    it("display more details when user click on show error", async () => {
        const error_message = "Full error message with details";
        const wrapper = await createWrapper(error_message);

        await wrapper.get("[data-test=show-details]").trigger("click");

        const details = wrapper.get("[data-test=details]");
        expect(details.text()).toBe(error_message);
    });

    it("warns user that something is wrong without any details", async () => {
        const wrapper = await createWrapper("");
        expect(wrapper.find("[data-test=show-details]").exists()).toBe(false);
        expect(wrapper.find("[data-test=details]").exists()).toBe(false);
    });
});
