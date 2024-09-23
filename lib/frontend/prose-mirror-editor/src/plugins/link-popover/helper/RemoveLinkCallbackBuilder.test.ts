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
import { EditorState } from "prosemirror-state";
import { custom_schema } from "../../../custom_schema";
import { RemoveLinkCallbackBuilder } from "./RemoveLinkCallbackBuilder";
import { createLocalDocument } from "../../../helpers";
import * as link_remover from "../../../helpers/remove-link";
import * as popover_remover from "./create-link-popover";

const editor_id = "aaaa-bbbb-cccc-dddd";

describe("RemoveLinkCallbackBuilder", () => {
    it("should return a callback which removes the link mark and removes the link popover", () => {
        const doc = createLocalDocument();
        const removeLink = vi.spyOn(link_remover, "removeLink");
        const removePopover = vi.spyOn(popover_remover, "removePopover");

        const state = EditorState.create({
            schema: custom_schema,
        });

        const dispatch = (): void => {
            // Do nothing
        };
        const callback = RemoveLinkCallbackBuilder(state, dispatch).build(doc, editor_id);

        callback();

        expect(removeLink).toHaveBeenCalledOnce();
        expect(removeLink).toHaveBeenCalledWith(state, state.schema.marks.link, dispatch);

        expect(removePopover).toHaveBeenCalledOnce();
        expect(removePopover).toHaveBeenCalledWith(doc, editor_id);
    });
});
