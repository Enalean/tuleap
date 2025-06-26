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

import { shallowMount } from "@vue/test-utils";
import { describe, it, expect } from "vitest";
import TlpColorBubble from "@/components/section/readonly-fields/TlpColorBubble.vue";

describe("TlpColorBubble", () => {
    it("Given a tlp color name, then it should display a bubble", () => {
        const wrapper = shallowMount(TlpColorBubble, {
            props: {
                tlp_color: "fiesta-red",
            },
        });

        expect(wrapper.find("[data-test=tlp-color-bubble]").classes()).toStrictEqual([
            "tlp-color-bubble",
            "document-color-bubble",
            "tlp-swatch-fiesta-red",
        ]);
    });
});
