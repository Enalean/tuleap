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

import { beforeAll, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import type { ComponentPublicInstance } from "vue";
import TableOfContents from "@/components/TableOfContents.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";

describe("TableOfContents", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        const defaultSection = ArtidocSectionFactory.create();
        wrapper = shallowMount(TableOfContents, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            propsData: {
                sections: [
                    ArtidocSectionFactory.override({
                        title: "Title 1",
                        artifact: { ...defaultSection.artifact, id: 1 },
                    }),
                    ArtidocSectionFactory.override({
                        title: "Title 2",
                        artifact: { ...defaultSection.artifact, id: 2 },
                    }),
                ],
            },
        });
    });

    it("should display the two title sections", () => {
        const list = wrapper.find("ol");
        expect(list.findAll("li")).toHaveLength(2);
        expect(list.text()).toContain("Title 1");
        expect(list.text()).toContain("Title 2");
    });

    it("should have an url to redirect to the section", () => {
        const list = wrapper.find("ol");
        const links = list.findAll("li a");
        expect(links.length).toBe(2);
        expect(links[0].attributes().href).toBe("#1");
        expect(links[1].attributes().href).toBe("#2");
    });
});
