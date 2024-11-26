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

import type { Transaction, EditorState } from "prosemirror-state";
import { TextSelection } from "prosemirror-state";
import type { UpdatedCrossReference } from "../../../helpers/UpdatedCrossReferenceTransactionDispatcher";
import type { RetrieveMarkExtents } from "./MarkExtentsRetriever";
type HandleUpdatedCrossReference = {
    handle(state: EditorState, updated_cross_reference: UpdatedCrossReference): Transaction | null;
};
export const UpdatedCrossReferenceHandler = (
    compute_mark_extents: RetrieveMarkExtents,
    project_id: number,
): HandleUpdatedCrossReference => ({
    handle: (state, updated_cross_reference): Transaction | null => {
        const { cross_reference_text, position } = updated_cross_reference;
        const extents = compute_mark_extents.retrieveExtentsOfMarkAtPosition(
            state.schema.marks.async_cross_reference,
            position,
        );

        if (!extents) {
            return null;
        }

        const { from, to } = extents;
        const transaction = state.tr;

        transaction.setSelection(
            new TextSelection(state.doc.resolve(from), state.doc.resolve(to + 1)),
        );
        transaction.replaceSelectionWith(state.schema.text(cross_reference_text));
        transaction.addMark(
            from,
            from + cross_reference_text.length,
            state.schema.marks.async_cross_reference.create({
                text: cross_reference_text,
                project_id,
            }),
        );

        return transaction;
    },
});
