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
import { Paragraph, TextRun, UnderlineType } from "docx";

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

    it("transforms inline markup style elements", () => {
        const paragraphs = transformHTMLIntoParagraphs(
            "<em>A</em><i>B</i><strong>C</strong><b>D</b><sup>E</sup><sub>F</sub><u>G</u>"
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [
                    new TextRun({ text: "A", italics: true }),
                    new TextRun({ text: "B", italics: true }),
                    new TextRun({ text: "C", bold: true }),
                    new TextRun({ text: "D", bold: true }),
                    new TextRun({ text: "E", superScript: true }),
                    new TextRun({ text: "F", subScript: true }),
                    new TextRun({ text: "G", underline: { type: UnderlineType.SINGLE } }),
                    new TextRun({ break: 1 }),
                ],
            }),
        ]);
    });

    it("transforms inline nested markup elements", () => {
        const paragraphs = transformHTMLIntoParagraphs(
            "<span><strong>A<em>B</em>C</strong></span>D"
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [
                    new TextRun({ text: "A", bold: true }),
                    new TextRun({ text: "B", bold: true, italics: true }),
                    new TextRun({ text: "C", bold: true }),
                    new TextRun({ text: "D" }),
                    new TextRun({ break: 1 }),
                ],
            }),
        ]);
    });

    it("transforms unordered lists", () => {
        const paragraphs = transformHTMLIntoParagraphs(
            "<ul><li>A<ul><li>A.1</li><li><strong>A.2</strong></li></ul></li><li>B</li></ul>"
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                bullet: { level: 0 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.1" })],
                bullet: { level: 1 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.2", bold: true })],
                bullet: { level: 1 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                bullet: { level: 0 },
            }),
        ]);
    });
});
