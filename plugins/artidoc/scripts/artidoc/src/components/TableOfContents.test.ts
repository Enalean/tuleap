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

import { beforeAll, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import type { ComponentPublicInstance } from "vue";
import { ref } from "vue";
import TableOfContents from "@/components/TableOfContents.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import * as sectionsStore from "@/stores/useSectionsStore";

describe("TableOfContents", () => {
    describe("when the sections are loading", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            const defaultSection = ArtidocSectionFactory.create();
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue({
                loadSections: vi.fn(),
                is_sections_loading: ref(true),
                sections: ref([
                    ArtidocSectionFactory.override({
                        artifact: { ...defaultSection.artifact, id: 1 },
                    }),
                    ArtidocSectionFactory.override({
                        artifact: { ...defaultSection.artifact, id: 2 },
                    }),
                ]),
            });

            wrapper = shallowMount(TableOfContents, {
                global: {
                    plugins: [createGettext({ silent: true })],
                },
            });
        });
        it("should display the skeleton content", () => {
            expect(wrapper.findAll('span[class="tlp-skeleton-text"]')).toHaveLength(2);
            expect(wrapper.find("a").exists()).toBe(false);
        });
        it("should display the table of content title", () => {
            expect(wrapper.find("h1").text()).toBe("Table of contents");
        });
    });

    describe("when the sections are loaded", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            const defaultSection = ArtidocSectionFactory.create();
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue({
                loadSections: vi.fn(),
                is_sections_loading: ref(false),
                sections: ref([
                    ArtidocSectionFactory.override({
                        artifact: { ...defaultSection.artifact, id: 1 },
                    }),
                    ArtidocSectionFactory.override({
                        artifact: { ...defaultSection.artifact, id: 2 },
                    }),
                ]),
            });

            wrapper = shallowMount(TableOfContents, {
                global: {
                    plugins: [createGettext({ silent: true })],
                },
            });
        });
        it("should display the two title sections", () => {
            const list = wrapper.findAll("li");
            expect(list).toHaveLength(2);
            expect(list[0].find("a").text()).toBe("Technologies section");
            expect(list[1].find("a").text()).toBe("Technologies section");
        });
        it("should have an url to redirect to the section", () => {
            const list = wrapper.find("ol");
            const links = list.findAll("li a");
            expect(links.length).toBe(2);
            expect(links[0].attributes().href).toBe("#1");
            expect(links[1].attributes().href).toBe("#2");
        });
        it("should display the table of content title", () => {
            expect(wrapper.find("h1").text()).toBe("Table of contents");
        });
    });
});
