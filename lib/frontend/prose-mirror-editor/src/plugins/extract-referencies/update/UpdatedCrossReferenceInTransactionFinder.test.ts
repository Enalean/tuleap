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

import { describe, it, expect, beforeEach } from "vitest";
import { EditorState } from "prosemirror-state";
import { UPDATED_CROSS_REFERENCE_TRANSACTION } from "../../../helpers/UpdatedCrossReferenceTransactionDispatcher";
import type { UpdatedCrossReference } from "../../../helpers/UpdatedCrossReferenceTransactionDispatcher";
import { EditorTextNodeCreator } from "../../../helpers/EditorTextNodeCreator";
import { custom_schema } from "../../../custom_schema";
import { UpdatedCrossReferenceInTransactionFinder } from "./UpdatedCrossReferenceInTransactionFinder";
import type { FindUpdatedCrossReferenceInTransaction } from "./UpdatedCrossReferenceInTransactionFinder";

describe("UpdatedCrossReferenceInTransactionFinder", () => {
    let finder: FindUpdatedCrossReferenceInTransaction, state: EditorState;

    beforeEach(() => {
        finder = UpdatedCrossReferenceInTransactionFinder();
        state = EditorState.create({
            schema: custom_schema,
        });
    });

    it("Given a collection of transaction, When it contains an UpdatedCrossReference transaction, then it should return its UpdatedCrossReference", () => {
        const updated_cross_reference: UpdatedCrossReference = {
            position: 12,
            new_reference_node: EditorTextNodeCreator(state).create("art #123"),
        };

        const transactions = [
            state.tr.setMeta("some-meta", "some-value"),
            state.tr.setMeta(UPDATED_CROSS_REFERENCE_TRANSACTION, updated_cross_reference),
            state.tr,
        ];

        expect(finder.find(transactions)).toBe(updated_cross_reference);
    });

    it("should return null when no UpdatedCrossReference transaction is found", () => {
        const transactions = [state.tr.setMeta("some-meta", "some-value"), state.tr];

        expect(finder.find(transactions)).toBeNull();
    });
});
