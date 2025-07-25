/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import LoadAllConfirmationModal from "./LoadAllConfirmationModal.vue";
import LoadAllButton from "./LoadAllButton.vue";

describe("LoadAllButton", () => {
    function getWrapper(): VueWrapper {
        return shallowMount(LoadAllButton, {
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it("should not display a confirmation modal", () => {
        const wrapper = getWrapper();
        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);

        expect(confirmation_modal.exists()).toBe(false);
    });

    it("should display a confirmation modal, when the 'Load all' button is clicked", async () => {
        const wrapper = getWrapper();

        wrapper.find("[data-test=load-all-button]").trigger("click");
        await wrapper.vm.$nextTick();

        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);
        expect(confirmation_modal.exists()).toBe(true);
    });

    it(`when should-load-all event is triggered with true
        it should not display the confirmation modal
        AND it should emit an load-all event
        AND it should disabled the 'Load all' button
        `, async () => {
        const wrapper = getWrapper();
        const load_all_button = wrapper.find("[data-test=load-all-button]");

        expect(load_all_button.attributes("disabled")).toBeUndefined();

        load_all_button.trigger("click");
        await wrapper.vm.$nextTick();

        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);
        confirmation_modal.vm.$emit("should-load-all", true);
        await wrapper.vm.$nextTick();

        expect(confirmation_modal.exists()).toBe(false);

        const load_all_event = wrapper.emitted("load-all");
        if (!load_all_event) {
            throw new Error("Expected a load-all event");
        }

        expect(load_all_event[0]).toStrictEqual([]);
        expect(load_all_button.attributes("disabled")).toBeDefined();
    });

    it("should not display the confirmation modal, and it should not emit an load-all event, when should-load-all event is triggered with false", async () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=load-all-button]").trigger("click");
        await wrapper.vm.$nextTick();

        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);
        confirmation_modal.vm.$emit("should-load-all", false);
        await wrapper.vm.$nextTick();

        expect(confirmation_modal.exists()).toBe(false);

        const load_all_event = wrapper.emitted("load-all");
        expect(load_all_event).toBeUndefined();
    });
});
