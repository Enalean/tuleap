/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
import ColorBadge from "./ColorBadge.vue";
import type { StaticListItem } from "@tuleap/plugin-tracker-rest-api-types";
import { StaticListItemTestBuilder } from "../../tests/builders/StaticListItemTestBuilder";

describe("ColorBadge", () => {
    it("displays the color badge if the value has an associated color", () => {
        const value: StaticListItem = StaticListItemTestBuilder.aStaticListItem(1)
            .withColor("red-wine")
            .build();
        const wrapper = shallowMount(ColorBadge, {
            props: {
                value,
            },
        });
        const color_element = wrapper.find("[data-test=input-box-colored-element]");
        expect(color_element.classes().includes("tlp-swatch-red-wine")).toBe(true);
    });
    it("does not displays the color badge if the value does not have a color", () => {
        const value: StaticListItem = StaticListItemTestBuilder.aStaticListItem(1).build();
        const wrapper = shallowMount(ColorBadge, {
            props: {
                value,
            },
        });
        const color_element = wrapper.find("[data-test=input-box-colored-element]");
        expect(color_element.exists()).toBe(false);
    });
});
