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

import type { EditorState, Transaction } from "prosemirror-state";
import { match_all_references_regexp } from "../regexps";

type LoadAllCrossReferences = {
    loadAllCrossReferences(): Transaction;
};

export const AllCrossReferencesLoader = (
    state: EditorState,
    project_id: number,
): LoadAllCrossReferences => ({
    loadAllCrossReferences: (): Transaction => {
        const transaction = state.tr;
        const schema = state.schema;

        state.doc.nodesBetween(0, state.doc.content.size, (node, position) => {
            if (
                node.type !== schema.nodes.text ||
                node.text === undefined ||
                state.schema.marks.link.isInSet(node.marks)
            ) {
                return;
            }

            const node_text = node.text;
            const matches = node_text.matchAll(match_all_references_regexp);

            for (const match of matches) {
                if (match.index === undefined) {
                    continue;
                }

                const reference = match[0];
                const from = position + match.index;
                const to = from + reference.length;

                transaction.addMark(
                    from,
                    to,
                    schema.marks.async_cross_reference.create({ text: reference, project_id }),
                );
            }
        });

        return transaction;
    },
});
