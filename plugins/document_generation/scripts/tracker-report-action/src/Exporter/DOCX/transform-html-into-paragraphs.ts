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

import type { IRunPropertiesOptions, ParagraphChild, ImageRun } from "docx";
import {
    AlignmentType,
    convertInchesToTwip,
    ExternalHyperlink,
    LevelFormat,
    Paragraph,
    TextRun,
    UnderlineType,
} from "docx";
import { loadImage } from "./Image/image-loader";

const HTML_ORDERED_LIST_NUMBERING_REFERENCE = "html-ordered-list";

export async function transformHTMLIntoParagraphs(content: string): Promise<Paragraph[]> {
    const doc = new DOMParser().parseFromString(content, "text/html");

    return buildParagraphsFromTreeContent(
        await parseTreeContent(doc.body.childNodes, { style: {}, list_level: 0 }),
        defaultParagraphBuilder
    );
}

type TreeContentChild = Paragraph | ParagraphChild;

type ParagraphBuilder = (children: ParagraphChild[]) => Paragraph;

function defaultParagraphBuilder(children: ParagraphChild[]): Paragraph {
    return new Paragraph({ children: [...children, new TextRun({ break: 1 })] });
}

function buildParagraphsFromTreeContent(
    tree_content: TreeContentChild[],
    builder: ParagraphBuilder
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
            child
        );
        top_level_paragraph_children.splice(0);
    }

    paragraphs.push(...buildParagraphFromParagraphChildren(builder, top_level_paragraph_children));

    return paragraphs;
}

function buildParagraphFromParagraphChildren(
    builder: ParagraphBuilder,
    paragraph_children: ParagraphChild[]
): Paragraph[] {
    if (paragraph_children.length <= 0) {
        return [];
    }

    return [builder(paragraph_children)];
}

interface TreeContentState {
    style: IRunPropertiesOptions;
    list_level: number;
}

async function parseTreeContent(
    tree: NodeListOf<ChildNode>,
    state: Readonly<TreeContentState>
): Promise<TreeContentChild[]> {
    const content_children: TreeContentChild[] = [];

    for (const child of tree) {
        if (!(child instanceof Element)) {
            content_children.push(...defaultNodeHandling(child, state));
            continue;
        }
        switch (child.nodeName) {
            case "DIV":
                content_children.push(...(await parseTreeContent(child.childNodes, state)));
                break;
            case "P":
                content_children.push(
                    ...buildParagraphsFromTreeContent(
                        await parseTreeContent(child.childNodes, state),
                        defaultParagraphBuilder
                    )
                );
                break;
            case "BR":
                content_children.push(new TextRun({ break: 1 }));
                break;
            case "SPAN":
                content_children.push(...(await parseTreeContent(child.childNodes, state)));
                break;
            case "EM":
            case "I":
                content_children.push(
                    ...(await parseTreeContent(child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            italics: true,
                        },
                    }))
                );
                break;
            case "STRONG":
            case "B":
                content_children.push(
                    ...(await parseTreeContent(child.childNodes, {
                        ...state,
                        style: { ...state.style, bold: true },
                    }))
                );
                break;
            case "SUP":
                content_children.push(
                    ...(await parseTreeContent(child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            superScript: true,
                        },
                    }))
                );
                break;
            case "SUB":
                content_children.push(
                    ...(await parseTreeContent(child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            subScript: true,
                        },
                    }))
                );
                break;
            case "U":
                content_children.push(
                    ...(await parseTreeContent(child.childNodes, {
                        ...state,
                        style: {
                            ...state.style,
                            underline: { type: UnderlineType.SINGLE },
                        },
                    }))
                );
                break;
            case "UL":
                for (const list_item of child.childNodes) {
                    content_children.push(
                        ...buildParagraphsFromTreeContent(
                            await parseTreeContent(list_item.childNodes, {
                                ...state,
                                list_level: state.list_level + 1,
                            }),
                            (children: ParagraphChild[]): Paragraph =>
                                new Paragraph({ children, bullet: { level: state.list_level } })
                        )
                    );
                }
                break;
            case "OL":
                for (const list_item of child.childNodes) {
                    content_children.push(
                        ...buildParagraphsFromTreeContent(
                            await parseTreeContent(list_item.childNodes, {
                                ...state,
                                list_level: state.list_level + 1,
                            }),
                            (children: ParagraphChild[]): Paragraph =>
                                new Paragraph({
                                    children,
                                    numbering: {
                                        level: state.list_level,
                                        reference: HTML_ORDERED_LIST_NUMBERING_REFERENCE,
                                    },
                                })
                        )
                    );
                }
                break;
            case "IMG":
                content_children.push(...(await getImageRun(child)));
                break;
            case "A":
                content_children.push(...(await getHyperLink(child, state)));
                break;
            default:
                content_children.push(...defaultNodeHandling(child, state));
        }
    }

    return content_children;
}

async function getImageRun(element: Element): Promise<ImageRun[]> {
    const source_image = element.getAttribute("src");
    if (source_image === null) {
        return [];
    }

    try {
        return [await loadImage(source_image)];
    } catch (e) {
        return [];
    }
}

async function getHyperLink(
    element: Element,
    state: Readonly<TreeContentState>
): Promise<TreeContentChild[]> {
    if (!(element instanceof HTMLAnchorElement) || element.href === "") {
        return parseTreeContent(element.childNodes, state);
    }

    const children = await parseTreeContent(element.childNodes, {
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

function defaultNodeHandling(node: Node, state: Readonly<TreeContentState>): TextRun[] {
    if (node.textContent === null || node.textContent === "") {
        return [];
    }
    return [new TextRun({ text: node.textContent, ...state.style })];
}

// This is based on the default implementation of the bullets to have a consistent rendering between unordered and
// ordered lists
// See https://github.com/dolanmiu/docx/blob/7.1.1/src/file/numbering/numbering.ts#L58-L158
export const HTML_ORDERED_LIST_NUMBERING = {
    levels: [
        {
            level: 0,
            format: LevelFormat.DECIMAL,
            text: "%1.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: convertInchesToTwip(0.5), hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 1,
            format: LevelFormat.DECIMAL,
            text: "%2.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: convertInchesToTwip(1), hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 2,
            format: LevelFormat.DECIMAL,
            text: "%3.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 2160, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 3,
            format: LevelFormat.DECIMAL,
            text: "%4.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 2880, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 4,
            format: LevelFormat.DECIMAL,
            text: "%5.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 3600, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 5,
            format: LevelFormat.DECIMAL,
            text: "%6.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 4320, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 6,
            format: LevelFormat.DECIMAL,
            text: "%7.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 5040, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 7,
            format: LevelFormat.DECIMAL,
            text: "%8.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 5760, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
        {
            level: 8,
            format: LevelFormat.DECIMAL,
            text: "%9.",
            alignment: AlignmentType.LEFT,
            style: {
                paragraph: {
                    indent: { left: 6480, hanging: convertInchesToTwip(0.25) },
                },
            },
        },
    ],

    reference: HTML_ORDERED_LIST_NUMBERING_REFERENCE,
};
