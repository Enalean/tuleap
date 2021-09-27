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

import { transformHTMLIntoParagraphs } from "./transform-html-into-paragraphs";
import { Paragraph, TextRun } from "docx";

describe("transform-html-into-paragraph", () => {
    it("transforms paragraphs that are the root of the document", () => {
        const paragraphs = transformHTMLIntoParagraphs("<p>A</p><p><span>B</span><br>C</p><p></p>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A"), new TextRun({ break: 1 })] }),
            new Paragraph({
                children: [
                    new TextRun("B"),
                    new TextRun({ break: 1 }),
                    new TextRun({ text: "C" }),
                    new TextRun({ break: 1 }),
                ],
            }),
        ]);
    });

    it("transforms phrasing content that are the root of the document", () => {
        const paragraphs = transformHTMLIntoParagraphs("A<p>B</p>C");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A"), new TextRun({ break: 1 })] }),
            new Paragraph({ children: [new TextRun("B"), new TextRun({ break: 1 })] }),
            new Paragraph({ children: [new TextRun("C"), new TextRun({ break: 1 })] }),
        ]);
    });

    it("traverses div tags when transforming the content", () => {
        const paragraphs = transformHTMLIntoParagraphs("<div><div>A<p>B</p></div></div>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A"), new TextRun({ break: 1 })] }),
            new Paragraph({ children: [new TextRun("B"), new TextRun({ break: 1 })] }),
        ]);
    });
});
