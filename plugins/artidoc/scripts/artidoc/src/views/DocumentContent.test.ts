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
import DocumentContent from "@/views/DocumentContent.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import SectionTitleWithArtifactId from "@/components/SectionTitleWithArtifactId.vue";

describe("DocumentContent", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        const defaultSection = ArtidocSectionFactory.create();

        wrapper = shallowMount(DocumentContent, {
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

    it("should contains section titles", () => {
        const list = wrapper.find("ol");
        const sectionTitles = list.findAllComponents(SectionTitleWithArtifactId);
        expect(sectionTitles).toHaveLength(2);
        expect(sectionTitles[0].attributes().title).toBe("Title 1");
        expect(sectionTitles[0].attributes().artifact_id).toBe("1");
        expect(sectionTitles[1].attributes().title).toBe("Title 2");
        expect(sectionTitles[1].attributes().artifact_id).toBe("2");
    });

    it("sections should have an id for anchor feature", () => {
        const list = wrapper.find("ol");
        const sections = list.findAll("li");
        expect(sections[0].attributes().id).toBe("1");
        expect(sections[1].attributes().id).toBe("2");
    });
});
