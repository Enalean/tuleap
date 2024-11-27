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

import { Fragment, Slice } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import type { SplitTextNodeWithReferences } from "./TextNodeWithReferencesSplitter";
import { match_single_reference_regexp } from "../regexps";

export type TransformPastedReferences = {
    transformPastedCrossReferencesToMark(pasted_slice: Slice): Slice;
};

const doesTextContainAtLeastOneReference = (text: string): boolean =>
    match_single_reference_regexp.test(text);

export const PastedReferencesTransformer = (
    split_text_node: SplitTextNodeWithReferences,
): TransformPastedReferences => ({
    transformPastedCrossReferencesToMark: (pasted_slice): Slice => {
        const detectAndTransformCrossReferences = (fragment: Fragment): Fragment => {
            const nodes: EditorNode[] = [];

            fragment.forEach((node: EditorNode): void => {
                if (node.isText) {
                    if (node.text && doesTextContainAtLeastOneReference(node.text)) {
                        nodes.push(...split_text_node.split(node));
                        return;
                    }

                    nodes.push(node.copy(node.content));
                    return;
                }

                nodes.push(node.copy(detectAndTransformCrossReferences(node.content)));
            });

            return Fragment.from(nodes);
        };

        return new Slice(
            detectAndTransformCrossReferences(pasted_slice.content),
            pasted_slice.openStart,
            pasted_slice.openEnd,
        );
    },
});
