/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return {
        autocomplete_projects_for_select2(): void {
            //do nothing
        },
    };
});

import { shallowMount } from "@vue/test-utils";
import UnrestictionConfirmationModal from "./UnrestictionConfirmationModal.vue";
import { createGettext } from "vue3-gettext";

describe("UnrestictionConfirmationModal", () => {
    it("should cancel restriction if user closes the modal", async () => {
        const wrapper = shallowMount(UnrestictionConfirmationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.emitted("cancel-unrestriction")).toBeFalsy();

        await wrapper.find("[data-dismiss=modal]").trigger("click");

        expect(wrapper.emitted("cancel-unrestriction")).toBeTruthy();
    });

    it("should inform user that something is happening when user confirm the unrestriction", async () => {
        const wrapper = shallowMount(UnrestictionConfirmationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.find("[data-test=submit-icon]").classes()).toContain("fa-save");

        await wrapper.find("[data-test=submit]").trigger("click");

        expect(wrapper.find("[data-test=submit-icon]").classes()).toContain("fa-circle-notch");
    });
});
