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
import * as popover_remover from "./create-link-popover";
import { createLocalDocument } from "../../../helpers";
import { EditCrossReferenceCallbackBuilder } from "./EditCrossReferenceCallbackBuilder";
import { DispatchCrossReferenceUpdatedTransactionStub } from "../../../helpers/stubs/DispatchCrossReferenceUpdatedTransactionStub";
import { EditorTextNodeCreator } from "../../../helpers/EditorTextNodeCreator";
import { buildCustomSchema } from "../../../custom_schema";
import { EditorState } from "prosemirror-state";

const editor_id = "aaaa-bbbb-cccc-dddd";
const cross_reference_text = "art #123";
const cursor_position = 12;

describe("EditCrossReferenceCallbackBuilder", () => {
    it("should return a callback which dispatches an updated-cross-reference transaction and removes the link popover", () => {
        const doc = createLocalDocument();
        const removePopover = vi.spyOn(popover_remover, "removePopover");

        const dispatcher = DispatchCrossReferenceUpdatedTransactionStub();
        const callback = EditCrossReferenceCallbackBuilder(
            dispatcher,
            EditorTextNodeCreator(EditorState.create({ schema: buildCustomSchema() })),
        ).build(doc, editor_id, cursor_position);

        callback(cross_reference_text);

        const dispatched_reference = dispatcher.getDispatchedReference();
        expect(dispatched_reference?.position).toBe(cursor_position);
        expect(dispatched_reference?.new_reference_node.textContent).toBe(cross_reference_text);

        expect(removePopover).toHaveBeenCalledOnce();
        expect(removePopover).toHaveBeenCalledWith(doc, editor_id);
    });
});
