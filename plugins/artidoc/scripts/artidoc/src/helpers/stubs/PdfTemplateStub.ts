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

import type { PdfTemplate } from "@tuleap/print-as-pdf";

export const PdfTemplateStub = {
    redTemplate: (): PdfTemplate => ({
        id: "abc",
        label: "Red template",
        description: "",
        style: "body { color: red }",
        header_content: "<em>ACME company</em>",
        footer_content: "<em>For motherland comrades eyes only</em>",
    }),
    blueTemplate: (): PdfTemplate => ({
        id: "abc",
        label: "Blue template",
        description: "",
        style: "body { color: blue }",
        header_content: "<b>I'M BLUE</b>",
        footer_content: "<b>Da Ba Dee Da Ba Da</b>",
    }),
};
