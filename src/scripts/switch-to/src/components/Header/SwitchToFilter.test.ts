/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import { createSwitchToLocalVue } from "../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../vue-components/store-wrapper-jest";
import { State } from "../../store/type";
import SwitchToFilter from "./SwitchToFilter.vue";
import { createModal, Modal } from "tlp";

jest.useFakeTimers();

describe("SwitchToFilter", () => {
    let modal: Modal;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        modal = createModal(doc.createElement("div"));
    });

    it("Saves the entered value in the store", async () => {
        const wrapper = shallowMount(SwitchToFilter, {
            localVue: await createSwitchToLocalVue(),
            propsData: {
                modal,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        filter_value: "",
                    } as State,
                }),
            },
        });

        if (wrapper.element instanceof HTMLInputElement) {
            wrapper.element.value = "abc";
        }
        await wrapper.trigger("keyup");

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("updateFilterValue", "abc");
    });

    it("Reset the value if the modal is closed", async () => {
        const wrapper = shallowMount(SwitchToFilter, {
            localVue: await createSwitchToLocalVue(),
            propsData: {
                modal,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        filter_value: "abc",
                    } as State,
                }),
            },
        });

        modal.hide();

        // There is a TRANSITION_DURATION before listeners are awakened
        jest.advanceTimersByTime(300);

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("updateFilterValue", "");
    });

    it("Closes the modal if the user hit [esc]", async () => {
        const hide = jest.spyOn(modal, "hide");

        const wrapper = shallowMount(SwitchToFilter, {
            localVue: await createSwitchToLocalVue(),
            propsData: {
                modal,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        filter_value: "abc",
                    } as State,
                }),
            },
        });

        await wrapper.trigger("keyup", { key: "Escape" });

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("updateFilterValue", "");
        expect(hide).toHaveBeenCalled();
    });
});
