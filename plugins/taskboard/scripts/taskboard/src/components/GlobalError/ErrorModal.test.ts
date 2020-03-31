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

import { Vue } from "vue/types/vue";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createTaskboardLocalVue } from "../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import ErrorModal from "./ErrorModal.vue";
import * as tlp from "tlp";
import { Modal } from "tlp";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        modal: jest.fn(),
    };
});

describe("ErrorModal", () => {
    let local_vue: typeof Vue;

    beforeEach(async () => {
        local_vue = await createTaskboardLocalVue();
    });

    function createWrapper(error_message: string): Wrapper<ErrorModal> {
        return shallowMount(ErrorModal, {
            localVue: local_vue,
            mocks: {
                $store: createStoreMock({
                    state: { error: { modal_error_message: error_message } },
                }),
            },
        });
    }

    it("warns user that something is wrong with a button to show details", () => {
        const actual_tlp = jest.requireActual("tlp");
        jest.spyOn(tlp, "modal").mockImplementation(actual_tlp.modal);
        const wrapper = createWrapper("Full error message with details");
        expect(wrapper.element).toMatchSnapshot();
    });

    it(`shows the modal when mounted`, () => {
        const modal_show = jest.fn();
        jest.spyOn(tlp, "modal").mockImplementation(() => {
            return ({
                show: modal_show,
            } as unknown) as Modal;
        });
        createWrapper("Full error message with details");
        expect(modal_show).toHaveBeenCalledTimes(1);
    });

    it("display more details when user click on show error", async () => {
        const error_message = "Full error message with details";
        const wrapper = createWrapper(error_message);

        wrapper.get("[data-test=show-details]").trigger("click");
        await wrapper.vm.$nextTick();

        const details = wrapper.get("[data-test=details]");
        expect(details.text()).toEqual(error_message);
    });

    it("warns user that something is wrong without any details", () => {
        const wrapper = createWrapper("");
        expect(wrapper.find("[data-test=show-details]").exists()).toBe(false);
        expect(wrapper.find("[data-test=details]").exists()).toBe(false);
    });
});
