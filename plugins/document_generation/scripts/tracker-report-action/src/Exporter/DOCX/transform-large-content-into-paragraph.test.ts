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

import { transformLargeContentIntoAParagraph } from "./transform-large-content-into-paragraph";
import { Paragraph, TextRun } from "docx";
import * as html_transformer from "./transform-html-into-paragraph";

describe("transform-large-content-into-paragraph", () => {
    it("transforms large plaintext content", () => {
        const paragraph = transformLargeContentIntoAParagraph("My\ncontent", "plaintext");

        expect(paragraph).toStrictEqual(
            new Paragraph({
                children: [
                    new TextRun("My"),
                    new TextRun({
                        text: "content",
                        break: 1,
                    }),
                ],
            })
        );
    });

    it("transforms large HTML content", () => {
        const expected_value = new Paragraph("Some HTML");
        jest.spyOn(html_transformer, "transformHTMLIntoAParagraph").mockReturnValue(expected_value);

        const paragraph = transformLargeContentIntoAParagraph("Some HTML", "html");

        expect(paragraph).toStrictEqual(expected_value);
    });
});
