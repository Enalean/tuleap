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
import type { ComponentPublicInstance } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentContent from "@/components/DocumentContent.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";

describe("DocumentContent", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        const defaultSection = ArtidocSectionFactory.create();

        wrapper = shallowMount(DocumentContent, {
            slots: {
                "section-header": "<h1>section header</h1>",
                "section-content": "<p>section content</p>",
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

    it("should display the two sections", () => {
        const list = wrapper.find("ol");
        expect(list.findAll("li")).toHaveLength(2);
    });

    it("should contains section header", () => {
        const list = wrapper.findAll("article");
        expect(list).toHaveLength(2);
        expect(list[0].find("h1").text()).toBe("section header");
        expect(list[1].find("h1").text()).toBe("section header");
    });

    it("should contains section content", () => {
        const list = wrapper.findAll("article");
        expect(list).toHaveLength(2);
        expect(list[0].find("p").text()).toBe("section content");
        expect(list[1].find("p").text()).toBe("section content");
    });

    it("sections should have an id for anchor feature", () => {
        const list = wrapper.find("ol");
        const sections = list.findAll("li");
        expect(sections[0].attributes().id).toBe("1");
        expect(sections[1].attributes().id).toBe("2");
    });
});
