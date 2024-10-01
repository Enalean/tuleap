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

import { describe, it, expect, vi } from "vitest";
import {
    UPDATED_CROSS_REFERENCE_TRANSACTION,
    UpdatedCrossReferenceTransactionDispatcher,
} from "./UpdatedCrossReferenceTransactionDispatcher";
import type { EditorView } from "prosemirror-view";
import { EditorState } from "prosemirror-state";
import { custom_schema } from "../custom_schema";
import { EditorTextNodeCreator } from "./EditorTextNodeCreator";

describe("UpdatedCrossReferenceTransactionDispatcher", () => {
    it('Given an UpdatedCrossReference, then it should dispatch an "updated-cross-reference" transaction', () => {
        const dispatch = vi.fn();
        const state = EditorState.create({
            schema: custom_schema,
        });

        const dispatcher = UpdatedCrossReferenceTransactionDispatcher(
            {
                dispatch,
            } as unknown as EditorView,
            state,
        );

        const updated_cross_reference = {
            position: 115,
            new_reference_node: EditorTextNodeCreator(state).create("art #132"),
        };

        dispatcher.dispatch(updated_cross_reference);

        expect(dispatch).toHaveBeenCalledOnce();

        const transaction = dispatch.mock.calls[0][0];
        expect(transaction.getMeta(UPDATED_CROSS_REFERENCE_TRANSACTION)).toBe(
            updated_cross_reference,
        );
    });
});
