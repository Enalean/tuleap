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
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { shallowMount } from "@vue/test-utils";
import DocumentDropdown from "@/components/DocumentDropdown.vue";
import { createGettext } from "vue3-gettext";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";

vi.mock("@tuleap/tlp-dropdown");

const options = {
    global: {
        plugins: [createGettext({ silent: true })],
    },
};

describe("DocumentDropdown", () => {
    describe("Document configuration", () => {
        it("should display configuration modal when user can edit document", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [SECTIONS_STORE, InjectedSectionsStoreStub.withLoadedSections([])],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(true);
        });

        it("should not display configuration modal when user cannot edit document", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, false],
                [SECTIONS_STORE, InjectedSectionsStoreStub.withLoadedSections([])],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(false);
        });
    });

    describe("PDF export", () => {
        it("should not display PDF export when the sections are being loaded", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [SECTIONS_STORE, InjectedSectionsStoreStub.withLoadingSections()],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should not display PDF export when the loading of sections failed", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [SECTIONS_STORE, InjectedSectionsStoreStub.withSectionsInError()],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should not display PDF export when there is no sections", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [SECTIONS_STORE, InjectedSectionsStoreStub.withLoadedSections([])],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });

        it("should display PDF export when there is at least one artifact section", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [
                    SECTIONS_STORE,
                    InjectedSectionsStoreStub.withLoadedSections([ArtifactSectionFactory.create()]),
                ],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(true);
        });

        it("should not display PDF export when there is only pending sections", () => {
            mockStrictInject([
                [CAN_USER_EDIT_DOCUMENT, true],
                [
                    SECTIONS_STORE,
                    InjectedSectionsStoreStub.withLoadedSections([
                        PendingArtifactSectionFactory.create(),
                        PendingArtifactSectionFactory.create(),
                    ]),
                ],
            ]);

            const wrapper = shallowMount(DocumentDropdown, options);

            expect(wrapper.findComponent(PdfExportMenuItem).exists()).toBe(false);
        });
    });
});
