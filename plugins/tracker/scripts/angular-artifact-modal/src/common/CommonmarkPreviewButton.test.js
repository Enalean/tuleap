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
import localVue from "../helpers/local-vue.js";
import { setCatalog } from "../gettext-catalog.js";

describe("CommonmarkPreviewButton", () => {
    it.each([
        ["Edit", true, "fa-pencil-alt"],
        ["Preview", false, "fa-eye"],
    ])(
        `displays the '%s' button if the preview mode is %s`,
        (expected_button_label, is_in_preview_mode, expected_class) => {
            setCatalog({ getString: (expected_button_label) => expected_button_label });
            const wrapper = shallowMount(CommonmarkPreviewButton, {
                localVue,
                propsData: {
                    is_in_preview_mode,
                },
            });
            expect(wrapper.find("[data-test=button-commonmark-preview-icon]").classes()).toContain(
                expected_class
            );
            expect(wrapper.text()).toBe(expected_button_label);
        }
    );
});
