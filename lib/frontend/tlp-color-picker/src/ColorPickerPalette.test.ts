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
import ColorPickerPalette from "./ColorPickerPalette.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { NO_COLOR } from "./colors";

describe("ColorPickerPalette", () => {
    function getWrapper(
        current_color: string,
    ): VueWrapper<InstanceType<typeof ColorPickerPalette>> {
        return shallowMount(ColorPickerPalette, {
            props: { current_color, is_no_color_allowed: true },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    }

    it("Should display current color with a check symbol", () => {
        const wrapper = getWrapper("acid-green");

        const color_item = wrapper.find("[data-test-color=acid-green]");
        expect(color_item.exists()).toBe(true);
        expect(color_item.classes()).toContain("fa-solid");
        expect(color_item.classes()).toContain("fa-check");
    });

    it("Should emit an event when clicking on a color", () => {
        const wrapper = getWrapper(NO_COLOR);

        wrapper.find("[data-test-color=deep-blue]").trigger("click");

        const click_events = wrapper.emitted("color-update");
        expect(click_events).toHaveLength(1);
        expect((click_events ?? [])[0]).toStrictEqual(["deep-blue"]);
    });
});
