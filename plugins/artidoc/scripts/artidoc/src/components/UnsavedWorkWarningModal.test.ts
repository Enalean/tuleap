/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import UnsavedWorkWarningModal from "@/components/UnsavedWorkWarningModal.vue";

describe("UnsavedWorkWarningModal", () => {
    const getWrapper = (): VueWrapper =>
        shallowMount(UnsavedWorkWarningModal, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

    it("When user clicks [Cancel], Then it should emit a 'cancel' event", () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=cancel-button]").trigger("click");

        expect(wrapper.emitted("cancel")).toBeDefined();
    });

    it("When the user clicks the (x) button in the modal header, Then it should emit a 'cancel' event", () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=close-modal-button]").trigger("click");

        expect(wrapper.emitted("cancel")).toBeDefined();
    });

    it("When the user clicks [Continue anyway], Then it should emit a 'continue-anyway' event", () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=continue-anyway-button]").trigger("click");

        expect(wrapper.emitted("continue-anyway")).toBeDefined();
    });
});
