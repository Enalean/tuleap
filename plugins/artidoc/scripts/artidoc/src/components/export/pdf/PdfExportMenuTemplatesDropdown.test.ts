/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import {
    buildPdfTemplatesCollection,
    PDF_TEMPLATES_COLLECTION,
} from "@/pdf/pdf-templates-collection";
import { PdfTemplateStub } from "@/helpers/stubs/PdfTemplateStub";
import PdfExportMenuTemplatesDropdown from "./PdfExportMenuTemplatesDropdown.vue";
import type { PdfTemplate } from "@tuleap/print-as-pdf";

vi.mock("@tuleap/tlp-dropdown");

describe("PdfExportMenuTemplatesDropdown", () => {
    let printUsingTemplate: (template: PdfTemplate) => void, pdf_templates: PdfTemplate[];

    beforeEach(() => {
        printUsingTemplate = vi.fn();
        pdf_templates = [PdfTemplateStub.blueTemplate(), PdfTemplateStub.redTemplate()];
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(PdfExportMenuTemplatesDropdown, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [PDF_TEMPLATES_COLLECTION.valueOf()]:
                        buildPdfTemplatesCollection(pdf_templates),
                },
            },
            propsData: {
                print_using_template: printUsingTemplate,
            },
        });

    it("should display three menuitem if two templates (one for each template + one for the submenu)", () => {
        expect(getWrapper().findAll("[role=menuitem]")).toHaveLength(3);
    });

    it("When a pdf template is clicked, then it should call the printUsingTemplate callback", async () => {
        await getWrapper().find("[data-test=pdf-template-button]").trigger("click");

        expect(printUsingTemplate).toHaveBeenCalledOnce();
        expect(printUsingTemplate).toHaveBeenCalledWith(pdf_templates[0]);
    });
});
