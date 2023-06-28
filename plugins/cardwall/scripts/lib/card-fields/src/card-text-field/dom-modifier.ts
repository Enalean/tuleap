/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { HighlightedText } from "../highlight/HighlightedText";
import type { ClassifierType } from "../highlight/Classifier";

export const getHighlightedNodes = (
    doc: Document,
    classifier: ClassifierType,
    content: string
): ReadonlyArray<Text | HTMLSpanElement> =>
    classifier.classify(content).map((highlighted_text) => {
        if (!HighlightedText.isHighlight(highlighted_text)) {
            return new Text(highlighted_text.content);
        }
        const span = doc.createElement("span");
        span.className = "highlight";
        span.textContent = highlighted_text.content;
        return span;
    });

const replaceChildrenOfNode = (
    doc: Document,
    parent_node: Node,
    classifier: ClassifierType
): void => {
    const child_nodes = Array.from(parent_node.childNodes);
    child_nodes.forEach((child_node) => {
        if (!(child_node instanceof Text)) {
            return;
        }
        const highlighted_nodes = getHighlightedNodes(doc, classifier, child_node.data);
        child_node.replaceWith(...highlighted_nodes);
    });
};

const hasTextChildrenNodes = (node: Node): boolean => {
    return Array.from(node.childNodes).some((child_node) => child_node.nodeType === Node.TEXT_NODE);
};

const gatherNodesWithTextChildren = (content: DocumentFragment): Node[] => {
    const nodes: Node[] = [];
    if (hasTextChildrenNodes(content)) {
        nodes.push(content);
    }
    for (const current_node of content.childNodes) {
        if (hasTextChildrenNodes(current_node)) {
            nodes.push(current_node);
        }
    }
    return nodes;
};

export const getHighlightedDOM = (
    doc: Document,
    content: DocumentFragment,
    classifier: ClassifierType
): DocumentFragment => {
    const clone = content.cloneNode(true);
    const result = doc.createDocumentFragment();
    result.append(...clone.childNodes);

    gatherNodesWithTextChildren(result).forEach((current_node) =>
        replaceChildrenOfNode(doc, current_node, classifier)
    );
    return result;
};
