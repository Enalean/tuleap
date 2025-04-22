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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { ref } from "vue";
import { noop } from "@/helpers/noop";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { skeleton_sections_collection } from "@/helpers/get-skeleton-sections-collection";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import TableOfContents from "./TableOfContents.vue";
import { SECTIONS_BELOW_ARTIFACTS } from "@/sections-below-artifacts-injection-key";
import ReorderArrows from "@/components/sidebar/toc/ReorderArrows.vue";
import { LEVEL_1, LEVEL_2 } from "@/sections/levels/SectionsNumberer";

const display_level_section1 = "1.";
const display_level_section2 = "2.";
const display_level_section3 = "2.1.";

describe("TableOfContents", () => {
    let can_user_edit_document: boolean,
        is_loading_sections: boolean,
        sections: ReactiveStoredArtidocSection[],
        section_below_artifacts: string[];
    beforeEach(() => {
        can_user_edit_document = true;
        is_loading_sections = true;
        sections = [];
        section_below_artifacts = [];
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(TableOfContents, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [DOCUMENT_ID.valueOf()]: 123,
                    [SECTIONS_COLLECTION.valueOf()]:
                        SectionsCollectionStub.fromReactiveStoredArtifactSections(sections),
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                    [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: noop,
                    [IS_LOADING_SECTIONS.valueOf()]: ref(is_loading_sections),
                    [SECTIONS_STATES_COLLECTION.valueOf()]:
                        SectionsStatesCollectionStub.fromReactiveStoredArtifactSections(sections),
                    [SECTIONS_BELOW_ARTIFACTS.valueOf()]: ref(section_below_artifacts),
                },
            },
        });

    describe("when the sections are loading", () => {
        beforeEach(() => {
            is_loading_sections = true;
            sections = skeleton_sections_collection;
        });

        it("should display the skeleton content", () => {
            const wrapper = getWrapper();

            expect(wrapper.findAll('span[class="tlp-skeleton-text"]')).toHaveLength(
                skeleton_sections_collection.length,
            );
            expect(wrapper.find("a").exists()).toBe(false);
        });

        it("should display the table of content title", () => {
            expect(getWrapper().find("h1").text()).toBe("Table of contents");
        });
    });

    describe("when the sections are loaded", () => {
        let artifact_section_1: ReactiveStoredArtidocSection,
            artifact_section_2: ReactiveStoredArtidocSection,
            freetext_section: ReactiveStoredArtidocSection;

        beforeEach(() => {
            is_loading_sections = false;

            artifact_section_1 = ReactiveStoredArtidocSectionStub.fromSection(
                ArtifactSectionFactory.override({
                    artifact: { ...ArtifactSectionFactory.create().artifact, id: 1 },
                    display_level: display_level_section1,
                }),
            );
            artifact_section_2 = ReactiveStoredArtidocSectionStub.fromSection(
                ArtifactSectionFactory.override({
                    artifact: { ...ArtifactSectionFactory.create().artifact, id: 2 },
                    display_level: display_level_section2,
                }),
            );
            freetext_section = ReactiveStoredArtidocSectionStub.fromSection(
                FreetextSectionFactory.override({
                    title: "Freetext section",
                    display_level: display_level_section3,
                }),
            );

            sections = [artifact_section_1, artifact_section_2, freetext_section];
        });

        describe("when user can edit document", () => {
            beforeEach(() => {
                can_user_edit_document = true;
            });

            it("should have dragndrop grip to reorder sections", () => {
                expect(getWrapper().findAll("[data-test=dragndrop-grip]").length).toBe(3);
            });
            it("should have arrows to reorder sections without dragndrop", () => {
                expect(getWrapper().findAll("[data-test=reorder-arrows]").length).toBe(3);
            });
        });

        describe("when user cannot edit document", () => {
            beforeEach(() => {
                can_user_edit_document = false;
            });

            it("should NOT have dragndrop grip to reorder sections", () => {
                expect(getWrapper().findAll("[data-test=dragndrop-grip]").length).toBe(0);
            });
            it("should NOT have arrows to reorder sections", () => {
                expect(getWrapper().findAll("[data-test=reorder-arrows]").length).toBe(0);
            });
        });

        it("should display the two title sections", () => {
            const list = getWrapper().findAll("li");

            expect(list).toHaveLength(3);

            expect(list[0].find("a").text()).toBe("Technologies section");
            expect(list[1].find("a").text()).toBe("Technologies section");
            expect(list[2].find("a").text()).toBe("Freetext section");
        });

        it(`should show visual cues that it is being saved`, async () => {
            vi.useFakeTimers();

            const wrapper = getWrapper();
            const first_section = wrapper.findAll("li").at(0);
            if (first_section === undefined) {
                throw Error("Expected to find the first section");
            }

            const arrows = first_section.findComponent(ReorderArrows);
            arrows.vm.$emit("moving-section-up-or-down", [
                { internal_id: first_section.attributes("data-internal-id") },
            ]);
            await wrapper.vm.$nextTick();
            expect(first_section.classes()).toContain("section-being-saved");
            expect(first_section.classes()).toContain("with-hidden-move-controls");

            arrows.vm.$emit("moved-section-up-or-down", [
                { internal_id: first_section.attributes("data-internal-id") },
            ]);
            await wrapper.vm.$nextTick();
            expect(first_section.classes()).toContain("section-saved-with-success");
            expect(first_section.classes()).toContain("with-hidden-move-controls");

            vi.runOnlyPendingTimers();
            await wrapper.vm.$nextTick();
            expect(first_section.classes()).not.toContain("section-saved-with-success");
            expect(first_section.classes()).not.toContain("with-hidden-move-controls");
        });

        it(`should show a CSS class when its parent section is hovered`, async () => {
            sections = [
                ReactiveStoredArtidocSectionStub.fromSection(
                    FreetextSectionFactory.override({ level: LEVEL_1 }),
                ),
                ReactiveStoredArtidocSectionStub.fromSection(
                    ArtifactSectionFactory.override({ level: LEVEL_2 }),
                ),
            ];

            const wrapper = getWrapper();
            const parent_section = wrapper.findAll("[data-test=dragndrop-grip]").at(0);
            if (parent_section === undefined) {
                throw Error("Expected to find the parent section");
            }
            await parent_section.trigger("pointerenter");

            const child_section = wrapper.findAll("li").at(1);
            if (child_section === undefined) {
                throw Error("Expected to find the child section");
            }
            expect(child_section.classes()).toContain("child-of-hovered-parent");
        });

        it(`should show an icon to warn user of sections below artifacts`, () => {
            section_below_artifacts = [sections[1].value.internal_id];

            const wrapper = getWrapper();
            expect(wrapper.find("[data-test=warning-icon]").exists()).toBe(true);
        });

        it("should have an url to redirect to the section", () => {
            const list = getWrapper().find("ul");
            const links = list.findAll("li a");

            expect(links.length).toBe(3);

            expect(links[0].attributes().href).toBe(`#section-${artifact_section_1.value.id}`);
            expect(links[1].attributes().href).toBe(`#section-${artifact_section_2.value.id}`);
            expect(links[2].attributes().href).toBe(`#section-${freetext_section.value.id}`);
        });

        it("should display the table of content title", () => {
            expect(getWrapper().find("h1").text()).toBe("Table of contents");
        });

        it("should display the number according to display_level", () => {
            const display_levels = getWrapper().findAll("[data-test=display-level]");
            expect(display_levels[0].text()).toBe(display_level_section1);
            expect(display_levels[1].text()).toBe(display_level_section2);
            expect(display_levels[2].text()).toBe(display_level_section3);
        });
    });
});
