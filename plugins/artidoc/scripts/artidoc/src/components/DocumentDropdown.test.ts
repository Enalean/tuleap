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

import { describe, beforeEach, expect, it, vi } from "vitest";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentDropdown from "@/components/DocumentDropdown.vue";
import { createGettext } from "vue3-gettext";
import { ref } from "vue";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import ConfigurationModalTrigger from "@/components/configuration/ConfigurationModalTrigger.vue";
import {
    IS_LOADING_SECTIONS,
    IS_LOADING_SECTIONS_FAILED,
} from "@/is-loading-sections-injection-key";

vi.mock("@tuleap/tlp-dropdown");

describe("DocumentDropdown", () => {
    let is_loading_sections: boolean, is_loading_sections_failed: boolean;

    beforeEach(() => {
        is_loading_sections = false;
        is_loading_sections_failed = false;
    });

    function getWrapper(
        can_user_edit_document: boolean,
        sections_collection: SectionsCollection,
    ): VueWrapper {
        return shallowMount(DocumentDropdown, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_LOADING_SECTIONS.valueOf()]: ref(is_loading_sections),
                    [IS_LOADING_SECTIONS_FAILED.valueOf()]: ref(is_loading_sections_failed),
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                    [SECTIONS_COLLECTION.valueOf()]: sections_collection,
                },
            },
        });
    }

    describe("Document configuration", () => {
        it("should display configuration modal when user can edit document", () => {
            const wrapper = getWrapper(true, SectionsCollectionStub.withSections([]));

            expect(wrapper.findComponent(ConfigurationModalTrigger).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(true);
        });

        it("should not display configuration modal when user cannot edit document", () => {
            const wrapper = getWrapper(false, SectionsCollectionStub.withSections([]));

            expect(wrapper.findComponent(ConfigurationModalTrigger).exists()).toBe(false);
            expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(false);
        });
    });

    describe("PDF export", () => {
        it("should not display PDF export when the sections are being loaded", () => {
            is_loading_sections = true;

            const wrapper = getWrapper(true, SectionsCollectionStub.withSections([]));

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should not display PDF export when the loading of sections failed", () => {
            is_loading_sections_failed = true;

            const wrapper = getWrapper(true, SectionsCollectionStub.withSections([]));

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should not display PDF export when there is no sections", () => {
            const wrapper = getWrapper(true, SectionsCollectionStub.withSections([]));

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should display PDF export when there is at least one artifact section", () => {
            const wrapper = getWrapper(
                true,
                SectionsCollectionStub.withSections([ArtifactSectionFactory.create()]),
            );

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(true);
        });

        it("should not display PDF export when there is only pending sections", () => {
            const wrapper = getWrapper(
                true,
                SectionsCollectionStub.withSections([
                    PendingArtifactSectionFactory.create(),
                    PendingArtifactSectionFactory.create(),
                ]),
            );

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });
    });
});
