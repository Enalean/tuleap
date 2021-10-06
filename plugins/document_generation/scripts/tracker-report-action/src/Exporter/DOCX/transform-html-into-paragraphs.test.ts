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
import { ImageRun, Paragraph, TextRun, UnderlineType } from "docx";
import * as image_loader from "./Image/image-loader";

describe("transform-html-into-paragraph", () => {
    it("transforms paragraphs that are the root of the document", async () => {
        const paragraphs = await transformHTMLIntoParagraphs(
            "<p>A</p><p><span>B</span><br>C</p><p></p>"
        );

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

    it("transforms phrasing content that are the root of the document", async () => {
        const paragraphs = await transformHTMLIntoParagraphs("A<p>B</p>C");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A"), new TextRun({ break: 1 })] }),
            new Paragraph({ children: [new TextRun("B"), new TextRun({ break: 1 })] }),
            new Paragraph({ children: [new TextRun("C"), new TextRun({ break: 1 })] }),
        ]);
    });

    it("traverses div tags when transforming the content", async () => {
        const paragraphs = await transformHTMLIntoParagraphs("<div><div>A<p>B</p></div></div>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A"), new TextRun({ break: 1 })] }),
            new Paragraph({ children: [new TextRun("B"), new TextRun({ break: 1 })] }),
        ]);
    });

    it("transforms inline markup style elements", async () => {
        const paragraphs = await transformHTMLIntoParagraphs(
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

    it("transforms inline nested markup elements", async () => {
        const paragraphs = await transformHTMLIntoParagraphs(
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

    it("transforms unordered lists", async () => {
        const paragraphs = await transformHTMLIntoParagraphs(
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

    it("transforms ordered lists", async () => {
        const paragraphs = await transformHTMLIntoParagraphs(
            "<ol><li>A<ol><li>A.1</li><li><strong>A.2</strong></li></ol></li><li>B</li></ol>"
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                numbering: { level: 0, reference: "html-ordered-list" },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.1" })],
                numbering: { level: 1, reference: "html-ordered-list" },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.2", bold: true })],
                numbering: { level: 1, reference: "html-ordered-list" },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                numbering: { level: 0, reference: "html-ordered-list" },
            }),
        ]);
    });

    it("transforms mixed ordered and unordered lists", async () => {
        const paragraphs = await transformHTMLIntoParagraphs(
            "<ul><li>A<ol><li>A.1</li><li><strong>A.2</strong></li></ol></li><li>B</li></ul>"
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                bullet: { level: 0 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.1" })],
                numbering: { level: 1, reference: "html-ordered-list" },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.2", bold: true })],
                numbering: { level: 1, reference: "html-ordered-list" },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                bullet: { level: 0 },
            }),
        ]);
    });

    it("transforms images", async () => {
        const expected_image_run = new ImageRun({
            data: "Success",
            transformation: { width: 1, height: 1 },
        });
        jest.spyOn(image_loader, "loadImage").mockImplementation(
            (image_url: string): Promise<ImageRun> => {
                if (image_url === "/success") {
                    return Promise.resolve(expected_image_run);
                }
                throw new Error("Something bad has happened");
            }
        );

        const paragraphs = await transformHTMLIntoParagraphs(
            "<img src='/success' /><img src='/fail'>"
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [expected_image_run, new TextRun({ break: 1 })],
            }),
        ]);
    });
});
