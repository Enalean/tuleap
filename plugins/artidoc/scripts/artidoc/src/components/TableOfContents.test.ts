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
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";

describe("TableOfContents", () => {
    describe("when the sections are loading", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            const default_section = ArtifactSectionFactory.create();
            mockStrictInject([
                [
                    SECTIONS_STORE,
                    InjectedSectionsStoreStub.withLoadingSections([
                        ArtifactSectionFactory.override({
                            artifact: { ...default_section.artifact, id: 1 },
                        }),
                        ArtifactSectionFactory.override({
                            artifact: { ...default_section.artifact, id: 2 },
                        }),
                    ]),
                ],
            ]);

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
            const default_section = ArtifactSectionFactory.create();
            mockStrictInject([
                [
                    SECTIONS_STORE,
                    InjectedSectionsStoreStub.withLoadedSections([
                        ArtifactSectionFactory.override({
                            artifact: { ...default_section.artifact, id: 1 },
                        }),
                        ArtifactSectionFactory.override({
                            artifact: { ...default_section.artifact, id: 2 },
                        }),
                    ]),
                ],
            ]);

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
