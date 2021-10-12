/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { transformLargeContentIntoParagraphs } from "./transform-large-content-into-paragraphs";
import { Paragraph, TextRun, HeadingLevel } from "docx";
import * as html_transformer from "./transform-html-into-paragraphs";

describe("transform-large-content-into-paragraph", () => {
    it("transforms large plaintext content", async () => {
        const paragraph = await transformLargeContentIntoParagraphs("My\ncontent", "plaintext", {
            ordered_title_levels: [HeadingLevel.HEADING_5],
            unordered_list_reference: "some-list",
            ordered_list_reference: "some-list",
            monospace_font: "monospace",
        });

        expect(paragraph).toStrictEqual([
            new Paragraph({
                children: [
                    new TextRun("My"),
                    new TextRun({
                        text: "content",
                        break: 1,
                    }),
                ],
            }),
        ]);
    });

    it("transforms large HTML content", async () => {
        const expected_value = new Paragraph("Some HTML");
        jest.spyOn(html_transformer, "transformHTMLIntoParagraphs").mockResolvedValue([
            expected_value,
        ]);

        const paragraph = await transformLargeContentIntoParagraphs("Some HTML", "html", {
            ordered_title_levels: [HeadingLevel.HEADING_5],
            unordered_list_reference: "some-list",
            ordered_list_reference: "some-list",
            monospace_font: "monospace",
        });

        expect(paragraph).toStrictEqual([expected_value]);
    });
});
