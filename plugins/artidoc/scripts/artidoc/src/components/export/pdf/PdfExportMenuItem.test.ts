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

import { describe, beforeEach, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { createGettext } from "vue3-gettext";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import PrinterVersion from "@/components/print/PrinterVersion.vue";
import PdfExportMenuTemplatesDropdown from "./PdfExportMenuTemplatesDropdown.vue";
import type { SectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { EDITORS_COLLECTION, useSectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { initPdfTemplatesStore, PDF_TEMPLATES_STORE } from "@/stores/pdf-templates-store";
import { PdfTemplateStub } from "@/helpers/stubs/PdfTemplateStub";
import { TITLE } from "@/title-injection-key";

describe("PdfExportMenuItem", () => {
    let editors_collection: SectionEditorsStore;

    beforeEach(() => {
        editors_collection = useSectionEditorsStore();
    });

    it("should display disabled menuitem if user is anonymous", () => {
        const wrapper = shallowMount(PdfExportMenuItem, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_USER_ANONYMOUS.valueOf()]: true,
                    [EDITORS_COLLECTION.valueOf()]: editors_collection,
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_STORE.valueOf()]: initPdfTemplatesStore([
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
                        [EDITORS_COLLECTION.valueOf()]: editors_collection,
                        [TITLE.valueOf()]: "Test document",
                        [PDF_TEMPLATES_STORE.valueOf()]: initPdfTemplatesStore(templates),
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

    it("should display disabled menuitem when at least one section is in edition mode", () => {
        editors_collection.addEditor(
            ArtifactSectionFactory.create(),
            SectionEditorStub.inEditMode(),
        );

        const wrapper = shallowMount(PdfExportMenuItem, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_USER_ANONYMOUS.valueOf()]: false,
                    [EDITORS_COLLECTION.valueOf()]: editors_collection,
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_STORE.valueOf()]: initPdfTemplatesStore([
                        PdfTemplateStub.blueTemplate(),
                    ]),
                },
            },
        });

        expect(wrapper.findAll("[role=menuitem]")).toHaveLength(1);
        expect(wrapper.findComponent(PrinterVersion).exists()).toBe(false);
    });

    it("should display one menuitem if one template", () => {
        const wrapper = shallowMount(PdfExportMenuItem, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [IS_USER_ANONYMOUS.valueOf()]: false,
                    [EDITORS_COLLECTION.valueOf()]: editors_collection,
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_STORE.valueOf()]: initPdfTemplatesStore([
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
                    [EDITORS_COLLECTION.valueOf()]: editors_collection,
                    [TITLE.valueOf()]: "Test document",
                    [PDF_TEMPLATES_STORE.valueOf()]: initPdfTemplatesStore([
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
