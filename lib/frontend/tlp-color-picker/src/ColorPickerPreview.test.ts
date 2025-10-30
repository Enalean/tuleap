/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ColorPickerPreview from "./ColorPickerPreview.vue";
import { createGettext } from "vue3-gettext";
import { NO_COLOR } from "./colors";

describe("ColorPickerPreview", () => {
    function getWrapper(
        color: string,
        is_unsupported_color: boolean,
    ): VueWrapper<InstanceType<typeof ColorPickerPreview>> {
        return shallowMount(ColorPickerPreview, {
            props: { color, is_unsupported_color },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    it("Should display a warning when using legacy color system", () => {
        const wrapper = getWrapper("#fff", true);

        expect(wrapper.find("[data-test=preview-unsupported-color]").exists()).toBe(true);
    });

    it("Should display an image when no color", () => {
        const wrapper = getWrapper(NO_COLOR, false);

        expect(wrapper.find("[data-test=preview-no-color]").exists()).toBe(true);
    });

    it("Should display the currently selected color", () => {
        const wrapper = getWrapper("fiesta-red", false);

        const preview = wrapper.find("[data-test=preview-color]");
        expect(preview.exists()).toBe(true);
        expect(preview.classes()).toContain("color-picker-preview-fiesta-red");
    });
});
