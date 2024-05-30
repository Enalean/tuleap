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
import type { ComponentPublicInstance } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentContent from "@/components/DocumentContent.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import SectionContent from "@/components/SectionContent.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";

describe("DocumentContent", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;
    beforeAll(() => {
        const defaultSection = ArtidocSectionFactory.create();
        vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
            InjectedSectionsStoreStub.withLoadedSections([
                ArtidocSectionFactory.override({
                    title: { ...defaultSection.title, value: "Title 1" },
                    artifact: { ...defaultSection.artifact, id: 1 },
                }),
                ArtidocSectionFactory.override({
                    title: { ...defaultSection.title, value: "Title 2" },
                    artifact: { ...defaultSection.artifact, id: 2 },
                }),
            ]),
        );

        wrapper = shallowMount(DocumentContent);
    });
    it("should display the two sections", () => {
        const list = wrapper.find("ol");
        expect(list.findAllComponents(SectionContent)).toHaveLength(2);
    });
    it("sections should have an id for anchor feature", () => {
        const list = wrapper.find("ol");
        const sections = list.findAll("li");
        expect(sections[0].attributes().id).toBe("1");
        expect(sections[1].attributes().id).toBe("2");
    });
});
