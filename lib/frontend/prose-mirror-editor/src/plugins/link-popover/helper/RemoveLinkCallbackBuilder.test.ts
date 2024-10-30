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
import { buildCustomSchema } from "../../../custom_schema";
import { RemoveLinkCallbackBuilder } from "./RemoveLinkCallbackBuilder";
import { createLocalDocument } from "../../../helpers";
import * as popover_remover from "./create-link-popover";
import * as link_remover from "./remove-selected-links";

const editor_id = "aaaa-bbbb-cccc-dddd";

describe("RemoveLinkCallbackBuilder", () => {
    it("should return a callback which removes the link mark and removes the link popover", () => {
        const doc = createLocalDocument();
        const removeSelectedLinks = vi.spyOn(link_remover, "removeSelectedLinks");
        const removePopover = vi.spyOn(popover_remover, "removePopover");

        const state = EditorState.create({
            schema: buildCustomSchema(),
        });

        const dispatch = (): void => {
            // Do nothing
        };
        const callback = RemoveLinkCallbackBuilder(state, dispatch).build(doc, editor_id);

        callback();

        expect(removeSelectedLinks).toHaveBeenCalledOnce();
        expect(removeSelectedLinks).toHaveBeenCalledWith(state, dispatch);

        expect(removePopover).toHaveBeenCalledOnce();
        expect(removePopover).toHaveBeenCalledWith(doc, editor_id);
    });
});
