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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { createGettext } from "vue3-gettext";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import PrinterVersion from "@/components/print/PrinterVersion.vue";
import PdfExportMenuTemplatesDropdown from "./PdfExportMenuTemplatesDropdown.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import {
    buildPdfTemplatesCollection,
    PDF_TEMPLATES_COLLECTION,
} from "@/pdf/pdf-templates-collection";
import { PdfTemplateStub } from "@/helpers/stubs/PdfTemplateStub";
import { TITLE } from "@/title-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

describe("PdfExportMenuItem", () => {
    it("should display disabled menuitem if user is anonymous", () => {
        const wrapper = shallowMount(PdfExportMenuItem, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_USER_ANONYMOUS.valueOf()]: true,
                    [SECTIONS_STATES_COLLECTION.valueOf()]: SectionsStatesCollectionStub.build(),
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_COLLECTION.valueOf()]: buildPdfTemplatesCollection([
                        PdfTemplateStub.blueTemplate(),
                    ]),
                },
            },
        });

        const button = wrapper.findAll("[role=menuitem]");
        expect(button).toHaveLength(1);
        expect(button[0].attributes("disabled")).toBeDefined();
        expect(button[0].attributes("title")).toBe(
            "Please log in in order to be able to export as PDF",
        );
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(false);
    });

    it.each([[null], [[]]])(
        "should display disabled menuitem if no template defined: %s",
        (templates) => {
            const wrapper = shallowMount(PdfExportMenuItem, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [IS_USER_ANONYMOUS.valueOf()]: false,
                        [SECTIONS_STATES_COLLECTION.valueOf()]:
                            SectionsStatesCollectionStub.build(),
                        [TITLE.valueOf()]: "Test document",
                        [PDF_TEMPLATES_COLLECTION.valueOf()]:
                            buildPdfTemplatesCollection(templates),
                    },
                },
            });

            const button = wrapper.findAll("[role=menuitem]");
            expect(button).toHaveLength(1);
            expect(button[0].attributes("disabled")).toBeDefined();
            expect(button[0].attributes("title")).toBe(
                "No template was defined for export, please contact site administrator",
            );
            expect(wrapper.findComponent(PrinterVersion).exists()).toBe(false);
        },
    );

    it.each([
        [FreetextSectionFactory.create()],
        [FreetextSectionFactory.pending()],
        [ArtifactSectionFactory.create()],
        [PendingArtifactSectionFactory.create()],
    ])(
        "should display disabled menuitem when at least one section is in edition mode",
        (section) => {
            const sections_states = SectionsStatesCollectionStub.build();
            const stored_section = ReactiveStoredArtidocSectionStub.fromSection(section);

            sections_states.createStateForSection(stored_section);
            sections_states.getSectionState(stored_section.value).is_section_in_edit_mode.value =
                true;

            const wrapper = shallowMount(PdfExportMenuItem, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [IS_USER_ANONYMOUS.valueOf()]: false,
                        [SECTIONS_STATES_COLLECTION.valueOf()]: sections_states,
                        [TITLE.valueOf()]: "Test document",
                        [PDF_TEMPLATES_COLLECTION.valueOf()]: buildPdfTemplatesCollection([
                            PdfTemplateStub.blueTemplate(),
                        ]),
                    },
                },
            });

            expect(wrapper.findAll("[role=menuitem]")).toHaveLength(1);
            expect(wrapper.findComponent(PrinterVersion).exists()).toBe(false);
        },
    );

    it("should display one menuitem if one template", () => {
        const wrapper = shallowMount(PdfExportMenuItem, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_USER_ANONYMOUS.valueOf()]: false,
                    [SECTIONS_STATES_COLLECTION.valueOf()]: SectionsStatesCollectionStub.build(),
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_COLLECTION.valueOf()]: buildPdfTemplatesCollection([
                        PdfTemplateStub.blueTemplate(),
                    ]),
                },
            },
        });

        expect(wrapper.findAll("[role=menuitem]")).toHaveLength(1);
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(true);
    });

    it("should display the PdfExportMenuTemplatesDropdown when there are more than one template", () => {
        const wrapper = shallowMount(PdfExportMenuItem, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_USER_ANONYMOUS.valueOf()]: false,
                    [SECTIONS_STATES_COLLECTION.valueOf()]: SectionsStatesCollectionStub.build(),
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_COLLECTION.valueOf()]: buildPdfTemplatesCollection([
                        PdfTemplateStub.blueTemplate(),
                        PdfTemplateStub.redTemplate(),
                    ]),
                },
            },
        });

        expect(wrapper.findComponent(PdfExportMenuTemplatesDropdown).exists()).toBe(true);
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(true);
    });
});
