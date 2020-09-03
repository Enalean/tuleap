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
import AppFlamingParrot from "./AppFlamingParrot.vue";
import $ from "jquery";
import { createSwitchToLocalVue } from "../helpers/local-vue-for-test";
import { createStoreMock } from "../../../vue-components/store-wrapper-jest";
import { State } from "../store/type";

describe("AppFlamingParrot", () => {
    it("Autofocus the first input in the modal", async () => {
        const wrapper = shallowMount(AppFlamingParrot, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        filter_value: "",
                    } as State,
                }),
            },
            stubs: {
                "switch-to-header": {
                    template: "<input type='text' data-test='focus'/>",
                },
            },
        });

        const input = wrapper.find("[data-test=focus]").element;
        if (!(input instanceof HTMLElement)) {
            throw Error("input not found");
        }
        const focus = jest.spyOn(input, "focus");

        $(wrapper.element).trigger("shown");

        expect(focus).toHaveBeenCalled();
    });

    it("Loads the history when the modal is shown", async () => {
        const wrapper = shallowMount(AppFlamingParrot, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        filter_value: "",
                    } as State,
                }),
            },
        });

        $(wrapper.element).trigger("shown");

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("loadHistory");
    });

    it("Clears the filter value when modal is closed", async () => {
        const wrapper = shallowMount(AppFlamingParrot, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        filter_value: "",
                    } as State,
                }),
            },
        });

        $(wrapper.element).trigger("hidden");

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("updateFilterValue", "");
    });
});
