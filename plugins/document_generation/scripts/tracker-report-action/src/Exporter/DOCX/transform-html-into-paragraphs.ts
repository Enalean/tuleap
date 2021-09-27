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

import { Paragraph, TextRun } from "docx";
import type { ParagraphChild } from "docx";

export function transformHTMLIntoParagraphs(content: string): Paragraph[] {
    const doc = new DOMParser().parseFromString(content, "text/html");

    return parseTree(doc.body.childNodes, { top_level_phrasing_content: [] });
}

interface TreeState {
    readonly top_level_phrasing_content: ChildNode[];
}

function parseTree(tree: NodeListOf<ChildNode>, tree_state: TreeState): Paragraph[] {
    const paragraphs: Paragraph[] = [];
    for (const child of tree) {
        switch (child.nodeName) {
            case "DIV":
                paragraphs.push(...parseTree(child.childNodes, tree_state));
                break;
            case "P":
                paragraphs.push(
                    ...processTopLevelPhrasingContent(tree_state),
                    ...buildParagraphFromPhrasingContent(Array.from(child.childNodes))
                );
                break;
            default:
                tree_state.top_level_phrasing_content.push(child);
        }
    }

    paragraphs.push(...processTopLevelPhrasingContent(tree_state));

    return paragraphs;
}

function processTopLevelPhrasingContent(tree_state: TreeState): Paragraph[] {
    if (tree_state.top_level_phrasing_content.length <= 0) {
        return [];
    }
    const paragraphs = buildParagraphFromPhrasingContent(tree_state.top_level_phrasing_content);
    tree_state.top_level_phrasing_content.splice(0);

    return paragraphs;
}

function buildParagraphFromPhrasingContent(phrasing_content: ChildNode[]): Paragraph[] {
    const paragraph_children: ParagraphChild[] = parsePhrasingContent(phrasing_content);

    if (paragraph_children.length <= 0) {
        return [];
    }

    return [
        new Paragraph({
            children: [...paragraph_children, new TextRun({ break: 1 })],
        }),
    ];
}

function parsePhrasingContent(phrasing_content: ChildNode[]): ParagraphChild[] {
    const paragraph_children: ParagraphChild[] = [];

    for (const child of phrasing_content) {
        switch (child.nodeName) {
            case "BR":
                paragraph_children.push(new TextRun({ break: 1 }));
                break;
            default:
                if (child.textContent !== null && child.textContent !== "") {
                    paragraph_children.push(new TextRun(child.textContent));
                }
        }
    }

    return paragraph_children;
}
