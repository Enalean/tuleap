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

import { describe, it, expect, beforeEach } from "vitest";
import { buildPdfTemplatesCollection } from "@/pdf/pdf-templates-collection";
import type { PdfTemplatesCollection } from "@/pdf/pdf-templates-collection";
import { PdfTemplateStub } from "@/helpers/stubs/PdfTemplateStub";

describe("pdf-templates-collection", () => {
    let collection: PdfTemplatesCollection;

    beforeEach(() => {
        collection = buildPdfTemplatesCollection([
            PdfTemplateStub.redTemplate(),
            PdfTemplateStub.blueTemplate(),
        ]);
    });

    it("When no template is selected, then selected_template should be null", () => {
        expect(collection.selected_template.value).toBeNull();
    });

    it("When a template is selected, then selected_template should be set", () => {
        collection.setSelectedPdfTemplate(collection.list.value[1]);

        expect(collection.selected_template.value).toBe(collection.list.value[1]);
    });
});
