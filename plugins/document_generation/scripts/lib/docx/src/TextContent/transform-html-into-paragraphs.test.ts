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

import { describe, it, expect, vi } from "vitest";
import { transformHTMLIntoParagraphs } from "./transform-html-into-paragraphs";
import type { IRunPropertiesOptions } from "docx";
import {
    BorderStyle,
    convertInchesToTwip,
    ExternalHyperlink,
    HeadingLevel,
    ImageRun,
    Paragraph,
    Table,
    TableCell,
    TableRow,
    TextRun,
    UnderlineType,
    WidthType,
} from "docx";
import * as image_loader from "../Image/image-loader";
import * as style_extractor from "./extract-style-html-element";
import * as list_instance_id_generator from "./list-instance-id-generator";

describe("transform-html-into-paragraph", () => {
    it("transforms paragraphs that are the root of the document", async () => {
        const paragraphs = await transformHTML("<p>A</p><p><span>B</span><br>C</p><p></p>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A")] }),
            new Paragraph({
                children: [new TextRun("B"), new TextRun({ break: 1 }), new TextRun({ text: "C" })],
            }),
        ]);
    });

    it("transforms phrasing content that are the root of the document", async () => {
        const paragraphs = await transformHTML("A<p>B</p>C");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A")] }),
            new Paragraph({ children: [new TextRun("B")] }),
            new Paragraph({ children: [new TextRun("C")] }),
        ]);
    });

    it("traverses div tags when transforming the content", async () => {
        const paragraphs = await transformHTML("<div><div>A<p>B</p></div></div>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({ children: [new TextRun("A")] }),
            new Paragraph({ children: [new TextRun("B")] }),
        ]);
    });

    it("transforms inline markup style elements", async () => {
        const paragraphs = await transformHTML(
            "<em>A</em><i>B</i><strong>C</strong><b>D</b><sup>E</sup><sub>F</sub><u>G</u>",
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
                ],
            }),
        ]);
    });

    it("transforms inline nested markup elements", async () => {
        const paragraphs = await transformHTML("<span><strong>A<em>B</em>C</strong></span>D");

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [
                    new TextRun({ text: "A", bold: true }),
                    new TextRun({ text: "B", bold: true, italics: true }),
                    new TextRun({ text: "C", bold: true }),
                    new TextRun({ text: "D" }),
                ],
            }),
        ]);
    });

    it("transforms unordered lists", async () => {
        setListInstanceIDGenerator();
        const paragraphs = await transformHTML(
            "<ul><li>A<ul><li>A.1</li><li><strong>A.2</strong></li></ul>                   </li><li>B</li></ul>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                numbering: { level: 0, reference: "html-unordered-list", instance: 90000 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.1" })],
                numbering: { level: 1, reference: "html-unordered-list", instance: 90001 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.2", bold: true })],
                numbering: { level: 1, reference: "html-unordered-list", instance: 90001 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                numbering: { level: 0, reference: "html-unordered-list", instance: 90000 },
            }),
        ]);
    });

    it("transforms ordered lists", async () => {
        setListInstanceIDGenerator();
        const paragraphs = await transformHTML(
            "<ol><li>A<ol><li>A.1</li><li><strong>A.2</strong></li></ol></li><li>B</li></ol>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                numbering: { level: 0, reference: "html-ordered-list", instance: 90000 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.1" })],
                numbering: { level: 1, reference: "html-ordered-list", instance: 90001 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.2", bold: true })],
                numbering: { level: 1, reference: "html-ordered-list", instance: 90001 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                numbering: { level: 0, reference: "html-ordered-list", instance: 90000 },
            }),
        ]);
    });

    it("transforms mixed ordered and unordered lists", async () => {
        setListInstanceIDGenerator();
        const paragraphs = await transformHTML(
            "<ul><li>A<ol><li>A.1</li><li><strong>A.2</strong></li></ol></li><li>B</li></ul>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                numbering: { level: 0, reference: "html-unordered-list", instance: 90000 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.1" })],
                numbering: { level: 1, reference: "html-ordered-list", instance: 90001 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "A.2", bold: true })],
                numbering: { level: 1, reference: "html-ordered-list", instance: 90001 },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                numbering: { level: 0, reference: "html-unordered-list", instance: 90000 },
            }),
        ]);
    });

    it("transforms images", async () => {
        const expected_image_run = new ImageRun({
            data: "Success",
            transformation: { width: 1, height: 1 },
        });
        vi.spyOn(image_loader, "loadImage").mockImplementation(
            (image_url: string): Promise<ImageRun> => {
                if (image_url === "/success") {
                    return Promise.resolve(expected_image_run);
                }
                throw new Error("Something bad has happened");
            },
        );

        const paragraphs = await transformHTML(
            "<img src='/success' /><img src='/fail'><img src='/fail2' alt='My image'>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [expected_image_run, new TextRun("My image")],
            }),
        ]);
    });

    it("transforms hyperlinks", async () => {
        const paragraphs = await transformHTML(
            "<a>A</a><a href='https://demo.example.com/'>B</a><a href='https://empty.example.com'></a>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [
                    new TextRun({ text: "A" }),
                    new ExternalHyperlink({
                        children: [new TextRun({ text: "B", style: "Hyperlink" })],
                        link: "https://demo.example.com/",
                    }),
                ],
            }),
        ]);
    });

    it("transforms titles", async () => {
        const paragraphs = await transformHTML("<h1>A</h1><h2>B</h2><h6>C</h6><p>D</p>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
                heading: HeadingLevel.HEADING_5,
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
                heading: HeadingLevel.HEADING_6,
            }),
            new Paragraph({
                children: [new TextRun({ text: "C" })],
                heading: HeadingLevel.HEADING_6,
            }),
            new Paragraph({
                children: [new TextRun({ text: "D" })],
            }),
        ]);
    });

    it("transforms horizontal rules", async () => {
        const paragraphs = await transformHTML("A<hr>B");

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A" })],
            }),
            new Paragraph({
                spacing: {
                    before: 100,
                    after: 100,
                    line: 0.25,
                },
                border: {
                    bottom: {
                        style: BorderStyle.SINGLE,
                        color: "000000",
                        size: 1,
                    },
                },
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
            }),
        ]);
    });

    it("transforms blockquotes", async () => {
        const paragraphs = await transformHTML("<blockquote><p>A</p></blockquote>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A", italics: true })],
                indent: {
                    left: convertInchesToTwip(0.25),
                },
            }),
        ]);
    });

    it("transforms code snippets", async () => {
        const paragraphs = await transformHTML(
            "<code>Inline</code><pre><code>Code\n  L2</code></pre>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "Inline", font: "Courier New" })],
            }),
            new Paragraph({
                children: [
                    new TextRun({ text: "Code", font: "Courier New" }),
                    new TextRun({ text: "  L2", break: 1, font: "Courier New" }),
                ],
            }),
        ]);
    });

    it("transforms tables", async () => {
        const paragraphs = await transformHTML(
            "<table><thead><tr><th>0.0</th></tr></thead><tbody><tr><td>1.0</td></tr></tbody></table>",
        );

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [
                    new Table({
                        rows: [
                            new TableRow({
                                children: [new TableCell({ children: [new Paragraph("0.0")] })],
                            }),
                            new TableRow({
                                children: [new TableCell({ children: [new Paragraph("1.0")] })],
                            }),
                        ],
                        width: {
                            size: 100,
                            type: WidthType.PERCENTAGE,
                        },
                    }),
                ],
            }),
        ]);
    });

    it("manages inlined styles", async () => {
        vi.spyOn(style_extractor, "extractInlineStyles").mockImplementation(
            (
                node: HTMLElement,
                source_style: Readonly<IRunPropertiesOptions>,
            ): IRunPropertiesOptions => {
                if (node.textContent === "A") {
                    return {
                        ...source_style,
                        color: "ff0000",
                    };
                }

                return source_style;
            },
        );

        const paragraphs = await transformHTML("<p style='color: red;'>A</p><p>B</p>");

        expect(paragraphs).toStrictEqual([
            new Paragraph({
                children: [new TextRun({ text: "A", color: "ff0000" })],
            }),
            new Paragraph({
                children: [new TextRun({ text: "B" })],
            }),
        ]);
    });

    it("cleans DOM before processing it", async () => {
        const paragraphs = await transformHTML(
            "<p><!--[if gte mso 9]><xml></xml><![endif]--></p><p>A</p>",
        );

        expect(paragraphs).toStrictEqual([new Paragraph({ children: [new TextRun("A")] })]);
    });
});

function transformHTML(content: string): Promise<Paragraph[]> {
    return transformHTMLIntoParagraphs(content, {
        ordered_title_levels: [HeadingLevel.HEADING_5, HeadingLevel.HEADING_6],
        ordered_list_reference: "html-ordered-list",
        unordered_list_reference: "html-unordered-list",
        monospace_font: "Courier New",
    });
}

function setListInstanceIDGenerator(): void {
    let list_instance_id = 90000;
    vi.spyOn(list_instance_id_generator, "getListInstanceID").mockImplementation(
        () => list_instance_id++,
    );
}
