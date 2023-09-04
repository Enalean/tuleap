/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import OpenClosedSwitcher from "./OpenClosedSwitcher.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createTaskboardLocalVue } from "../../../helpers/local-vue-for-test";

describe("OpenClosedSwitcher", () => {
    it("toggles the right button when closed items should be displayed", async () => {
        const wrapper = shallowMount(OpenClosedSwitcher, {
            localVue: await createTaskboardLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        are_closed_items_displayed: true,
                    },
                }),
            },
        });

        const radio_show: HTMLInputElement = wrapper.get("#button-bar-show-closed")
            .element as HTMLInputElement;
        const radio_hide: HTMLInputElement = wrapper.get("#button-bar-hide-closed")
            .element as HTMLInputElement;
        expect(radio_show.checked).toBe(true);
        expect(radio_hide.checked).toBe(false);
    });

    it("toggles the right button when closed items should not be displayed", async () => {
        const wrapper = shallowMount(OpenClosedSwitcher, {
            localVue: await createTaskboardLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        are_closed_items_displayed: false,
                    },
                }),
            },
        });

        const radio_show: HTMLInputElement = wrapper.get("#button-bar-show-closed")
            .element as HTMLInputElement;
        const radio_hide: HTMLInputElement = wrapper.get("#button-bar-hide-closed")
            .element as HTMLInputElement;
        expect(radio_show.checked).toBe(false);
        expect(radio_hide.checked).toBe(true);
    });

    it("Mutates the store when user decides to display closed items", async () => {
        const wrapper = shallowMount(OpenClosedSwitcher, {
            localVue: await createTaskboardLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        are_closed_items_displayed: false,
                    },
                }),
            },
        });
        wrapper.get("#button-bar-show-closed").setChecked();
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "displayClosedItems",
            expect.anything(),
        );
    });

    it("Mutates the store when user decides to hide closed items", async () => {
        const wrapper = shallowMount(OpenClosedSwitcher, {
            localVue: await createTaskboardLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        are_closed_items_displayed: false,
                    },
                }),
            },
        });
        const hide_button = wrapper.get("#button-bar-hide-closed");
        hide_button.setChecked();
        hide_button.trigger("change");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "hideClosedItems",
            expect.anything(),
        );
    });
});
