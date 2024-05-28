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
import { convertDescriptionToHtml } from "@/helpers/convert-description-to-html";

describe("convert-description-to-html", () => {
    it("should convert html to html", () => {
        expect(
            convertDescriptionToHtml({
                field_id: 1001,
                label: "Summary",
                type: "text",
                format: "html",
                value: "<p>Old art #1</p>",
                post_processed_value: "<p>Old <a href=''>art #1</a></p>",
            }),
        ).toBe("<p>Old art #1</p>");
    });

    it("should convert markdown to html", () => {
        expect(
            convertDescriptionToHtml({
                field_id: 1001,
                label: "Summary",
                type: "text",
                format: "html",
                value: "<p>Old <a href=''>art #1</a></p>",
                post_processed_value: "<p>Old <a href=''>art #1</a></p>",
                commonmark: "Old art #1",
            }),
        ).toBe("<p>Old art #1</p>\n");
    });

    it("should convert text to html", () => {
        expect(
            convertDescriptionToHtml({
                field_id: 1001,
                label: "Summary",
                type: "text",
                format: "text",
                value: "Old art #1",
                post_processed_value: "<p>Old <a href=''>art #1</a></p>",
            }),
        ).toBe("<p>Old art #1</p>\n");
    });
});
