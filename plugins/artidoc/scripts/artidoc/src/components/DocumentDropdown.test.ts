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
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentDropdown from "@/components/DocumentDropdown.vue";
import { createGettext } from "vue3-gettext";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { SectionsStore } from "@/stores/useSectionsStore";

vi.mock("@tuleap/tlp-dropdown");

describe("DocumentDropdown", () => {
    function getWrapper(
        can_user_edit_document: boolean,
        sections_store: SectionsStore,
    ): VueWrapper {
        return shallowMount(DocumentDropdown, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                    [SECTIONS_STORE.valueOf()]: sections_store,
                },
            },
        });
    }

    describe("Document configuration", () => {
        it("should display configuration modal when user can edit document", () => {
            const wrapper = getWrapper(true, InjectedSectionsStoreStub.withLoadedSections([]));

            expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(true);
        });

        it("should not display configuration modal when user cannot edit document", () => {
            const wrapper = getWrapper(false, InjectedSectionsStoreStub.withLoadedSections([]));

            expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(false);
        });
    });

    describe("PDF export", () => {
        it("should not display PDF export when the sections are being loaded", () => {
            const wrapper = getWrapper(true, InjectedSectionsStoreStub.withLoadingSections());

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should not display PDF export when the loading of sections failed", () => {
            const wrapper = getWrapper(true, InjectedSectionsStoreStub.withSectionsInError());

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should not display PDF export when there is no sections", () => {
            const wrapper = getWrapper(true, InjectedSectionsStoreStub.withLoadedSections([]));

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should display PDF export when there is at least one artifact section", () => {
            const wrapper = getWrapper(
                true,
                InjectedSectionsStoreStub.withLoadedSections([ArtifactSectionFactory.create()]),
            );

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(true);
        });

        it("should not display PDF export when there is only pending sections", () => {
            const wrapper = getWrapper(
                true,
                InjectedSectionsStoreStub.withLoadedSections([
                    PendingArtifactSectionFactory.create(),
                    PendingArtifactSectionFactory.create(),
                ]),
            );

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });
    });
});
