/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import { markActive } from "../plugins/toolbar/menu";
import { getWrappingNodeInfo } from "../plugins/toolbar/helper/node-info-retriever";
import type { EditorState, Transaction } from "prosemirror-state";
import { NodeSelection, TextSelection } from "prosemirror-state";
import type { MarkType, ResolvedPos } from "prosemirror-model";

function selectWholeTextLink(transaction: Transaction, from: ResolvedPos): void {
    transaction.setSelection(new NodeSelection(from));
}

function removeMarkOnLink(
    transaction: Transaction,
    from: ResolvedPos,
    to: ResolvedPos,
    mark_type: MarkType,
): void {
    transaction.removeMark(from.pos, to.pos, mark_type);
}

function putBackSelectionOnCursorPosition(
    transaction: Transaction,
    old_from: number,
    old_to: number,
): void {
    transaction.setSelection(
        new TextSelection(transaction.doc.resolve(old_from), transaction.doc.resolve(old_to)),
    );
}

export function removeLink(
    state: EditorState,
    mark_type: MarkType,
    dispatch: (tr: Transaction) => void,
): void {
    if (!markActive(state, mark_type)) {
        return;
    }

    const wrapping_node_info = getWrappingNodeInfo(state.selection.$from, mark_type, state);

    const old_from = state.selection.from;
    const old_to = state.selection.to;

    const from = state.doc.resolve(wrapping_node_info.from);
    const to = state.doc.resolve(wrapping_node_info.to);
    const transaction = state.tr;

    selectWholeTextLink(transaction, from);
    removeMarkOnLink(transaction, from, to, mark_type);
    putBackSelectionOnCursorPosition(transaction, old_from, old_to);

    dispatch(transaction.scrollIntoView());
}
