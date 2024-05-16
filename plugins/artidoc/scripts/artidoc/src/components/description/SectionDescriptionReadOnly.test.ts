/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import * as tooltip from "@tuleap/tooltip";
import { shallowMount } from "@vue/test-utils";
import SectionDescriptionReadOnly from "@/components/description/SectionDescriptionReadOnly.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";

describe("SectionDescriptionReadOnly", () => {
    it("should display text with tooltips", () => {
        const loadTooltips = vi.spyOn(tooltip, "loadTooltips");
        const wrapper = shallowMount(SectionDescriptionReadOnly, {
            props: {
                readonly_value: "Lorem ipsum",
            },
            global: {
                plugins: [VueDOMPurifyHTML],
            },
        });
        expect(wrapper.text()).toContain("Lorem ipsum");
        expect(loadTooltips).toHaveBeenCalled();
    });
});
