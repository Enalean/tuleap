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

import type {
    HeadingLevel,
    ImageRun,
    IRunPropertiesOptions,
    ParagraphChild,
    IRunStylePropertiesOptions,
} from "docx";
import {
    BorderStyle,
    convertInchesToTwip,
    ExternalHyperlink,
    Paragraph,
    Table,
    TableCell,
    TableRow,
    TextRun,
    UnderlineType,
    WidthType,
} from "docx";
import dompurify from "dompurify";
import { loadImage } from "../Image/image-loader";
import { transformTextWithNewlines } from "./transform-text-with-newlines";
import { extractInlineStyles } from "./extract-style-html-element";
import { getListInstanceID } from "./list-instance-id-generator";

export type ReadonlyArrayWithAtLeastOneElement<T> = { 0: T } & ReadonlyArray<T>;

export interface TransformationOptions {
    ordered_title_levels: ReadonlyArrayWithAtLeastOneElement<HeadingLevel>;
    ordered_list_reference: string;
    unordered_list_reference: string;
    monospace_font: Exclude<IRunStylePropertiesOptions["font"], undefined>;
}

export async function transformHTMLIntoParagraphs(
    content: string,
    options: TransformationOptions,
): Promise<Paragraph[]> {
    const doc = dompurify.sanitize(content, { RETURN_DOM_FRAGMENT: true });

    return buildParagraphsFromTreeContent(
        await parseTreeContent(options, doc.childNodes, {
            style: {},
            list_level: 0,
            paragraph_builder: defaultParagraphBuilder,
            text_run_builder: defaultTextRunBuilder,
        }),
        defaultParagraphBuilder,
    );
}

type TreeContentChild = Paragraph | ParagraphChild;

type ParagraphBuilder = (children: ParagraphChild[]) => Paragraph;

function defaultParagraphBuilder(children: ParagraphChild[]): Paragraph {
    return new Paragraph({ children });
}

type TextRunBuilder = (content: string, style: IRunPropertiesOptions) => TextRun[];

function defaultTextRunBuilder(content: string, style: IRunPropertiesOptions): TextRun[] {
    return [new TextRun({ text: content, ...style })];
}

function buildParagraphsFromTreeContent(
    tree_content: TreeContentChild[],
    builder: ParagraphBuilder,
): Paragraph[] {
    const paragraphs: Paragraph[] = [];

    const top_level_paragraph_children: ParagraphChild[] = [];

    for (const child of tree_content) {
        if (!(child instanceof Paragraph)) {
            top_level_paragraph_children.push(child);
            continue;
        }

        paragraphs.push(
            ...buildParagraphFromParagraphChildren(builder, top_level_paragraph_children),
            child,
        );
        top_level_paragraph_children.splice(0);
    }

    paragraphs.push(...buildParagraphFromParagraphChildren(builder, top_level_paragraph_children));

    return paragraphs;
}

function buildParagraphFromParagraphChildren(
    builder: ParagraphBuilder,
    paragraph_children: ParagraphChild[],
): Paragraph[] {
    if (paragraph_children.length <= 0) {
        return [];
    }

    return [builder(paragraph_children)];
}

interface TreeContentState {
    style: IRunPropertiesOptions;
    list_level: number;
    paragraph_builder: ParagraphBuilder;
    text_run_builder: TextRunBuilder;
}

async function parseTreeContent(
    options: TransformationOptions,
    tree: NodeListOf<ChildNode>,
    source_state: Readonly<TreeContentState>,
): Promise<TreeContentChild[]> {
    const content_children: TreeContentChild[] = [];

    for (const child of tree) {
        if (!(child instanceof HTMLElement)) {
            content_children.push(...defaultNodeHandling(child, source_state));
            continue;
        }
        const state = {
            ...source_state,
            style: extractInlineStyles(child, source_state.style),
        };
        switch (child.nodeName) {
            case "DIV":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, state)),
                );
                break;
            case "P":
                content_children.push(
                    ...buildParagraphsFromTreeContent(
                        await parseTreeContent(options, child.childNodes, state),
                        state.paragraph_builder,
                    ),
                );
                break;
            case "BR":
                content_children.push(new TextRun({ break: 1 }));
                break;
            case "SPAN":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, state)),
                );
                break;
            case "EM":
            case "I":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            italics: true,
                        },
                    })),
                );
                break;
            case "STRONG":
            case "B":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, {
                        ...state,
                        style: { ...state.style, bold: true },
                    })),
                );
                break;
            case "SUP":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            superScript: true,
                        },
                    })),
                );
                break;
            case "SUB":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            subScript: true,
                        },
                    })),
                );
                break;
            case "U":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            underline: { type: UnderlineType.SINGLE },
                        },
                    })),
                );
                break;
            case "UL":
            case "OL":
                content_children.push(...(await getList(child, state, options)));
                break;
            case "IMG":
                content_children.push(...(await getImageRun(child, state)));
                break;
            case "A":
                content_children.push(...(await getHyperLink(options, child, state)));
                break;
            case "H1":
            case "H2":
            case "H3":
            case "H4":
            case "H5":
            case "H6":
                content_children.push(
                    ...(await getTitle(
                        options,
                        parseInt(child.nodeName.charAt(1), 10),
                        child.childNodes,
                        state,
                    )),
                );
                break;
            case "HR":
                content_children.push(
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
                );
                break;
            case "BLOCKQUOTE":
                content_children.push(
                    ...(await parseTreeContent(options, child.childNodes, {
                        ...state,
                        style: { ...state.style, italics: true },
                        paragraph_builder: (children: ParagraphChild[]): Paragraph =>
                            new Paragraph({
                                children,
                                indent: {
                                    left: convertInchesToTwip(0.25),
                                },
                            }),
                    })),
                );
                break;
            case "CODE":
                content_children.push(
                    ...defaultNodeHandling(child, {
                        ...state,
                        style: { ...state.style, font: options.monospace_font },
                    }),
                );
                break;
            case "PRE":
                content_children.push(
                    ...buildParagraphsFromTreeContent(
                        await parseTreeContent(options, child.childNodes, {
                            ...state,
                            text_run_builder: transformTextWithNewlines,
                        }),
                        state.paragraph_builder,
                    ),
                );
                break;
            case "TABLE":
                content_children.push(
                    new Table({
                        rows: await getTableRows(child.getElementsByTagName("tr"), options, state),
                        width: {
                            size: 100,
                            type: WidthType.PERCENTAGE,
                        },
                    }),
                );
                break;
            default:
                content_children.push(...defaultNodeHandling(child, state));
        }
    }

    return content_children;
}

async function getList(
    element: Element,
    state: TreeContentState,
    options: TransformationOptions,
): Promise<Paragraph[]> {
    const content_children: Paragraph[] = [];
    const list_instance_id = getListInstanceID();
    for (const list_item of element.childNodes) {
        list_item.childNodes.forEach((child_node) => {
            if (child_node.nodeName === "#text" && child_node.textContent?.trim() === "") {
                child_node.textContent = "";
            }
        });
        content_children.push(
            ...buildParagraphsFromTreeContent(
                await parseTreeContent(options, list_item.childNodes, {
                    ...state,
                    list_level: state.list_level + 1,
                }),
                (children: ParagraphChild[]): Paragraph =>
                    new Paragraph({
                        children,
                        numbering: {
                            level: state.list_level,
                            reference:
                                element.nodeName === "UL"
                                    ? options.unordered_list_reference
                                    : options.ordered_list_reference,
                            instance: list_instance_id,
                        },
                    }),
            ),
        );
    }

    return content_children;
}

async function getImageRun(
    element: Element,
    state: TreeContentState,
): Promise<ImageRun[] | TextRun[]> {
    const source_image = element.getAttribute("src");
    if (source_image === null) {
        return [];
    }

    try {
        return [await loadImage(source_image)];
    } catch (e) {
        const alt_image = element.getAttribute("alt") ?? "";
        if (alt_image !== "") {
            return state.text_run_builder(alt_image, state.style);
        }
        return [];
    }
}

async function getHyperLink(
    options: TransformationOptions,
    element: Element,
    state: Readonly<TreeContentState>,
): Promise<TreeContentChild[]> {
    if (!(element instanceof HTMLAnchorElement) || element.href === "") {
        return parseTreeContent(options, element.childNodes, state);
    }

    const children = await parseTreeContent(options, element.childNodes, {
        ...state,
        style: {
            ...state.style,
            style: "Hyperlink",
        },
    });
    if (children.length <= 0) {
        return [];
    }
    return [new ExternalHyperlink({ children, link: element.href })];
}

async function getTitle(
    options: TransformationOptions,
    level: number,
    children: NodeListOf<ChildNode>,
    state: Readonly<TreeContentState>,
): Promise<TreeContentChild[]> {
    let heading_level = options.ordered_title_levels[level - 1];
    if (heading_level === undefined) {
        heading_level = options.ordered_title_levels.slice(-1)[0];
    }
    return buildParagraphsFromTreeContent(
        await parseTreeContent(options, children, state),
        (children: ParagraphChild[]): Paragraph => {
            return new Paragraph({
                children,
                heading: heading_level,
            });
        },
    );
}

async function getTableRows(
    html_rows: HTMLCollectionOf<HTMLTableRowElement>,
    options: TransformationOptions,
    state: Readonly<TreeContentState>,
): Promise<TableRow[]> {
    const rows: TableRow[] = [];
    for (const html_row of html_rows) {
        const cells = [...html_row.children].map(async (cell): Promise<TableCell> => {
            return new TableCell({
                children: buildParagraphsFromTreeContent(
                    await parseTreeContent(options, cell.childNodes, state),
                    state.paragraph_builder,
                ),
            });
        });
        rows.push(new TableRow({ children: await Promise.all(cells) }));
    }

    return rows;
}

function defaultNodeHandling(node: Node, state: Readonly<TreeContentState>): TextRun[] {
    if (node.textContent === null || node.textContent === "") {
        return [];
    }
    return state.text_run_builder(node.textContent, state.style);
}
