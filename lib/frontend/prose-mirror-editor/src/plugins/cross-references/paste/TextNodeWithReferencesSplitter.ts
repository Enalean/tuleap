/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Schema } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import { match_all_references_regexp } from "../regexps";

export type SplitTextNodeWithReferences = {
    split(node: EditorNode): EditorNode[];
};

type CrossReferenceTextNode = {
    node: EditorNode;
    index: number;
};

const doesSentenceStartWithACrossReference = (
    nodes_with_references: CrossReferenceTextNode[],
): boolean => {
    if (nodes_with_references.length === 0) {
        return false;
    }
    return nodes_with_references[0].index === 0;
};

const sortNodesInSentenceOrder = (
    text_parts_without_reference: EditorNode[],
    nodes_with_references: CrossReferenceTextNode[],
): EditorNode[] => {
    const sorted_nodes: EditorNode[] = [];
    const appendNextTextPartIfAny = (): void => {
        const text_part = text_parts_without_reference.shift();
        if (text_part) {
            sorted_nodes.push(text_part);
        }
    };

    if (doesSentenceStartWithACrossReference(nodes_with_references)) {
        nodes_with_references.forEach((reference) => {
            sorted_nodes.push(reference.node);
            appendNextTextPartIfAny();
        });

        return sorted_nodes;
    }

    nodes_with_references.forEach((reference) => {
        appendNextTextPartIfAny();
        sorted_nodes.push(reference.node);
    });

    appendNextTextPartIfAny();

    return sorted_nodes;
};

export const TextNodeWithReferencesSplitter = (
    schema: Schema,
    project_id: number,
): SplitTextNodeWithReferences => ({
    split: (node: EditorNode): EditorNode[] => {
        if (!node.isText || !node.text) {
            throw new Error("Provided node is not a Text node");
        }

        const node_text = node.text;

        const matches = node_text.matchAll(match_all_references_regexp);
        const text_parts_without_reference = match_all_references_regexp[Symbol.split](node_text)
            .filter((part) => part !== "")
            .map((part) => schema.text(part));

        const nodes_with_references: CrossReferenceTextNode[] = [];

        for (const match of matches) {
            if (match.index === undefined) {
                continue;
            }

            const reference = match[0];
            const mark = schema.marks.async_cross_reference.create({
                text: reference,
                project_id,
            });

            nodes_with_references.push({
                index: match.index,
                node: schema.text(reference).mark([mark]),
            });
        }

        return sortNodesInSentenceOrder(text_parts_without_reference, nodes_with_references);
    },
});
