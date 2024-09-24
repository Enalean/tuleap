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
import type { EditorView } from "prosemirror-view";
import { createLocalDocument } from "../../../helpers";
import { EditLinkCallbackBuilder } from "./EditLinkCallbackBuilder";
import * as popover_remover from "./create-link-popover";
import * as link_replacer from "../../../helpers/replace-link-node";

const editor_id = "aaaa-bbbb-cccc-dddd";

describe("EditLinkCallbackBuilder", () => {
    it("should return a callback which updates the link mark and removes the link popover", () => {
        const doc = createLocalDocument();
        const replaceLinkNode = vi
            .spyOn(link_replacer, "replaceLinkNode")
            .mockImplementation((): void => {
                // Do nothing
            });
        const removePopover = vi.spyOn(popover_remover, "removePopover");
        const link = {
            href: "https://example.com",
            title: "See example",
        };

        const view = {} as EditorView;
        const callback = EditLinkCallbackBuilder(view).build(doc, editor_id);

        callback(link);

        expect(replaceLinkNode).toHaveBeenCalledOnce();
        expect(replaceLinkNode).toHaveBeenCalledWith(view, link);

        expect(removePopover).toHaveBeenCalledOnce();
        expect(removePopover).toHaveBeenCalledWith(doc, editor_id);
    });
});
