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

import type { EditorState } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";

export const UPDATED_CROSS_REFERENCE_TRANSACTION = "updated-cross-reference";

export type UpdatedCrossReference = {
    readonly position: number;
    readonly cross_reference_text: string;
};

export type DispatchCrossReferenceUpdatedTransaction = {
    dispatch(updated_cross_reference: UpdatedCrossReference): void;
};

export const UpdatedCrossReferenceTransactionDispatcher = (
    view: EditorView,
    state: EditorState,
): DispatchCrossReferenceUpdatedTransaction => ({
    dispatch: (updated_cross_reference: UpdatedCrossReference): void => {
        const tr = state.tr;
        tr.setMeta(UPDATED_CROSS_REFERENCE_TRANSACTION, updated_cross_reference);

        view.dispatch(tr);
    },
});
