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

import type { Ref } from "vue";
import { ref } from "vue";
import type { PdfTemplate } from "@tuleap/print-as-pdf";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

export type PdfTemplatesCollection = {
    list: Ref<ReadonlyArray<PdfTemplate>>;
    selected_template: Ref<Readonly<PdfTemplate> | null>;
    setSelectedPdfTemplate(template: Readonly<PdfTemplate>): void;
};

export const PDF_TEMPLATES_COLLECTION: StrictInjectionKey<PdfTemplatesCollection> = Symbol(
    "pdf-templates-collection",
);

export const buildPdfTemplatesCollection = (
    templates: ReadonlyArray<PdfTemplate> | null,
): PdfTemplatesCollection => {
    const list = ref(templates ?? []);
    const selected_template: Ref<Readonly<PdfTemplate> | null> = ref(null);

    return {
        list,
        selected_template,
        setSelectedPdfTemplate: (template: PdfTemplate): void => {
            selected_template.value = template;
        },
    };
};
