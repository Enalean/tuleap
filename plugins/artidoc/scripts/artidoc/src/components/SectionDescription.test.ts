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
import { shallowMount } from "@vue/test-utils";
import SectionDescription from "@/components/SectionDescription.vue";
import * as tooltip from "@tuleap/tooltip";
import VueDOMPurifyHTML from "vue-dompurify-html";
import SectionDescriptionSkeleton from "@/components/SectionDescriptionSkeleton.vue";

const default_props = {
    description_value: "Lorem ipsum",
    is_sections_loading: false,
    artifact_id: 1,
};
const default_global = {
    plugins: [VueDOMPurifyHTML],
};
describe("SectionDescription", () => {
    describe("while the sections are loading", () => {
        it("should display the skeleton", () => {
            const wrapper = shallowMount(SectionDescription, {
                props: { ...default_props, is_sections_loading: true },
                global: default_global,
            });
            expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(true);
            expect(wrapper.find("div").exists()).toBe(false);
        });
    });

    it("should display text with tooltips", () => {
        const loadTooltips = vi.spyOn(tooltip, "loadTooltips");
        const wrapper = shallowMount(SectionDescription, {
            props: default_props,
            global: default_global,
        });
        expect(wrapper.text()).toContain("Lorem ipsum");
        expect(loadTooltips).toHaveBeenCalled();
    });
});
