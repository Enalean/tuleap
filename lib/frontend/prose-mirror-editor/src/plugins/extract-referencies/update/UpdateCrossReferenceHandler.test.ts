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

import { describe, beforeEach, it, expect } from "vitest";
import { EditorState } from "prosemirror-state";
import type { EditorNode } from "../../../types/internal-types";
import { custom_schema } from "../../../custom_schema";
import { createCrossReferenceDecoration } from "../../../helpers/create-cross-reference-decoration";
import { FindCrossReferenceDecorationStub } from "./stubs/FindCrossReferenceDecorationStub";
import { ReplaceCrossReferenceDecorationStub } from "./stubs/ReplaceCrossReferenceDecorationStub";
import { UpdateCrossReferenceHandler } from "./UpdateCrossReferenceHandler";

describe("UpdateCrossReferenceHandler", () => {
    let state: EditorState;

    beforeEach(() => {
        state = EditorState.create({ schema: custom_schema });
    });

    it("When no cross reference has been updated, then it should return null", () => {
        const new_transaction = UpdateCrossReferenceHandler(
            FindCrossReferenceDecorationStub.withoutDecoration(),
            ReplaceCrossReferenceDecorationStub.willNotReplace(),
        ).handle(null);

        expect(new_transaction).toBeNull();
    });

    it("When no cross reference decoration has been found at the updated reference position, then it should return null", () => {
        const new_transaction = UpdateCrossReferenceHandler(
            FindCrossReferenceDecorationStub.withoutDecoration(),
            ReplaceCrossReferenceDecorationStub.willNotReplace(),
        ).handle({
            new_reference_node: {} as EditorNode,
            position: 12,
        });

        expect(new_transaction).toBeNull();
    });

    it("should return a new transaction to replace the cross reference", () => {
        const expected_transaction = state.tr;
        const replace_stub =
            ReplaceCrossReferenceDecorationStub.willReplaceWithTransaction(expected_transaction);
        const updated_reference = {
            new_reference_node: {} as EditorNode,
            position: 12,
        };
        const replaced_decoration = createCrossReferenceDecoration(
            { from: 10, to: 18 },
            { text: "art #123", link: "https://example.com", context: "" },
        );
        const new_transaction = UpdateCrossReferenceHandler(
            FindCrossReferenceDecorationStub.withDecoration(replaced_decoration),
            replace_stub,
        ).handle(updated_reference);

        expect(new_transaction).toBe(expected_transaction);
        expect(replace_stub.getReplacingReference()).toStrictEqual(updated_reference);
        expect(replace_stub.getReplacedDecoration()).toStrictEqual(replaced_decoration);
    });
});
