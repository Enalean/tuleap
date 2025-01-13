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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionDropdown from "./SectionDropdown.vue";
import { createGettext } from "vue3-gettext";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { SectionEditor } from "@/composables/useSectionEditor";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { REMOVE_FREETEXT_SECTION_MODAL } from "@/composables/useRemoveFreetextSectionModal";

vi.mock("@tuleap/tlp-dropdown");
vi.mock("@/helpers/move-dropdownmenu-in-document-body");

describe("SectionDropdown", () => {
    function getWrapper(editor: SectionEditor, section: ArtidocSection): VueWrapper {
        return shallowMount(SectionDropdown, {
            propsData: {
                editor,
                section,
            },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIGURATION_STORE.valueOf()]: true,
                    [REMOVE_FREETEXT_SECTION_MODAL.valueOf()]: true,
                },
            },
        });
    }

    describe("when the user is allowed to edit the section", () => {
        it.each([
            ["artifact", ArtifactSectionFactory],
            ["freetext", FreetextSectionFactory],
        ])("should display a dropdown menu with a delete item for %s section", (name, factory) => {
            const wrapper = getWrapper(SectionEditorStub.withEditableSection(), factory.create());

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(true);
            expect(wrapper.find("[data-test=delete]").exists()).toBe(true);
        });

        it("should display a dropdown menu with a 'go to artifact' item for artifact section", () => {
            const wrapper = getWrapper(
                SectionEditorStub.withEditableSection(),
                ArtifactSectionFactory.create(),
            );

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(true);
            expect(wrapper.find("[data-test=go-to-artifact]").exists()).toBe(true);
        });

        it("should display a dropdown menu without a 'go to artifact' item for freetext section", () => {
            const wrapper = getWrapper(
                SectionEditorStub.withEditableSection(),
                FreetextSectionFactory.create(),
            );

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(true);
            expect(wrapper.find("[data-test=go-to-artifact]").exists()).toBe(false);
        });
    });

    describe("when the user is not allowed to edit the artifact section", () => {
        it("should hide delete menu item", () => {
            const wrapper = getWrapper(
                SectionEditorStub.withoutEditableSection(),
                ArtifactSectionFactory.create(),
            );

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(true);
            expect(wrapper.find("[data-test=delete]").exists()).toBe(false);
        });
    });

    describe("when the user is not allowed to edit the freetext section", () => {
        it("should not display the dropdown menu at all", () => {
            const wrapper = getWrapper(
                SectionEditorStub.withoutEditableSection(),
                FreetextSectionFactory.create(),
            );

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(false);
        });
    });

    describe("when the section is a pending artifact section", () => {
        it("should not display the dropdown", () => {
            const wrapper = getWrapper(
                SectionEditorStub.withEditableSection(),
                PendingArtifactSectionFactory.create(),
            );

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(false);
        });
    });

    describe("when the section is a pending freetext section", () => {
        it("should not display the dropdown", () => {
            const wrapper = getWrapper(
                SectionEditorStub.withEditableSection(),
                FreetextSectionFactory.pending(),
            );

            expect(wrapper.find("[data-test=artidoc-dropdown-trigger]").exists()).toBe(false);
        });
    });
});
