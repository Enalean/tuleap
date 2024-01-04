/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import CommonmarkPreviewButton from "./CommonmarkPreviewButton.vue";
import { createLocalVueForTests } from "../helpers/local-vue.js";

describe("CommonmarkPreviewButton", () => {
    it.each([
        ["Edit", true, "fa-pencil-alt"],
        ["Preview", false, "fa-eye"],
    ])(
        `displays the '%s' button if the preview mode is %s`,
        async (expected_button_label, is_in_preview_mode, expected_class) => {
            const wrapper = shallowMount(CommonmarkPreviewButton, {
                localVue: await createLocalVueForTests(),
                propsData: {
                    is_in_preview_mode,
                    is_preview_loading: false,
                },
            });

            const icon_classes = wrapper
                .find("[data-test=button-commonmark-preview-icon]")
                .classes();
            expect(icon_classes).toContain(expected_class);
            expect(icon_classes).not.toContain("fa-circle-notch");
            expect(icon_classes).not.toContain("fa-spin");
            expect(wrapper.text()).toBe(expected_button_label);
        },
    );

    it("disables the button and display the spinner when the preview is loading", async () => {
        const wrapper = shallowMount(CommonmarkPreviewButton, {
            localVue: await createLocalVueForTests(),
            propsData: {
                is_in_preview_mode: false,
                is_preview_loading: true,
            },
        });

        const icon_classes = wrapper.find("[data-test=button-commonmark-preview-icon]").classes();

        expect(icon_classes).toContain("fa-circle-notch");
        expect(icon_classes).toContain("fa-spin");

        expect(wrapper.find("[data-test=button-commonmark-preview]").element.disabled).toBe(true);
    });
});
