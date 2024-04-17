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
            slots: {
                default: "<p>section title</p>",
            },
            propsData: {
                sections: [
                    ArtidocSectionFactory.override({
                        artifact: { ...defaultSection.artifact, id: 1 },
                    }),
                    ArtidocSectionFactory.override({
                        artifact: { ...defaultSection.artifact, id: 2 },
                    }),
                ],
            },
        });
    });

    it("should display the table of content title", () => {
        expect(wrapper.find("h1").text()).toBe("Table of contents");
    });

    it("should display the two title sections", () => {
        const list = wrapper.findAll("li");
        expect(list).toHaveLength(2);
        expect(list[0].find("a").text()).toBe("section title");
        expect(list[1].find("a").text()).toBe("section title");
    });

    it("should have an url to redirect to the section", () => {
        const list = wrapper.find("ol");
        const links = list.findAll("li a");
        expect(links.length).toBe(2);
        expect(links[0].attributes().href).toBe("#1");
        expect(links[1].attributes().href).toBe("#2");
    });
});
