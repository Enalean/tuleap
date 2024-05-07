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
import SectionContent from "@/components/SectionContent.vue";
import { ref } from "vue";
import type { ComponentPublicInstance } from "vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import SectionTitleWithArtifactId from "@/components/SectionTitleWithArtifactId.vue";
import SectionDescription from "@/components/SectionDescription.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import SectionTitleWithArtifactIdSkeleton from "@/components/SectionTitleWithArtifactIdSkeleton.vue";
describe("SectionContent", () => {
    describe("when the sections are loaded", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue({
                loadSections: vi.fn(),
                is_sections_loading: ref(false),
                sections: ref([]),
            });
            wrapper = shallowMount(SectionContent, {
                props: {
                    section: ArtidocSectionFactory.create(),
                },
            });
        });
        it("should display a section title", () => {
            expect(wrapper.findComponent(SectionTitleWithArtifactId).exists()).toBe(true);
            expect(wrapper.findComponent(SectionTitleWithArtifactIdSkeleton).exists()).toBe(false);
        });
        it("should display a section description", () => {
            expect(wrapper.findComponent(SectionDescription).exists()).toBe(true);
        });
    });
    describe("when the sections are loading", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue({
                loadSections: vi.fn(),
                is_sections_loading: ref(true),
                sections: ref([]),
            });
            wrapper = shallowMount(SectionContent, {
                props: {
                    section: ArtidocSectionFactory.create(),
                },
            });
        });
        it("should display a skeleton section title", () => {
            expect(wrapper.findComponent(SectionTitleWithArtifactIdSkeleton).exists()).toBe(true);
            expect(wrapper.findComponent(SectionTitleWithArtifactId).exists()).toBe(false);
        });
    });
});
