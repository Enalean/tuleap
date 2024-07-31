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
import { initPdfTemplatesStore } from "@/stores/pdf-templates-store";
import type { PdfTemplatesStore } from "@/stores/pdf-templates-store";
import { PdfTemplateStub } from "@/helpers/stubs/PdfTemplateStub";

describe("pdf-templates-store", () => {
    let store: PdfTemplatesStore;

    beforeEach(() => {
        store = initPdfTemplatesStore([
            PdfTemplateStub.redTemplate(),
            PdfTemplateStub.blueTemplate(),
        ]);
    });

    it("When no template is selected, then selected_template should be null", () => {
        expect(store.selected_template.value).toBeNull();
    });

    it("When a template is selected, then selected_template should be set", () => {
        store.setSelectedPdfTemplate(store.list.value[1]);

        expect(store.selected_template.value).toBe(store.list.value[1]);
    });
});
