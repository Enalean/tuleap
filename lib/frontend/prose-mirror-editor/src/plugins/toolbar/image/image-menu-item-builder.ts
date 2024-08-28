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

import type { NodeType } from "prosemirror-model";
import type { GetText } from "@tuleap/gettext";
import { MenuItem } from "prosemirror-menu";
import type { EditorView } from "prosemirror-view";
import type { EditorState } from "prosemirror-state";
import { buildImagePopover } from "./popover-image";

function canInsert(state: EditorState, node_type: NodeType): boolean {
    const $from = state.selection.$from;
    for (let d = $from.depth; d >= 0; d--) {
        const index = $from.index(d);
        if ($from.node(d).canReplaceWith(index, index, node_type)) {
            return true;
        }
    }
    return false;
}

export function imageItem(
    node_type: NodeType,
    popover_element_id: string,
    gettext_provider: GetText,
): MenuItem {
    const image_alt_id = `image-alt-${popover_element_id}`;
    const image_src_id = `image-src-${popover_element_id}`;
    const image_popover_title_id = `image-title-${popover_element_id}`;
    const image_popover_submit_id = `image-popover-${popover_element_id}`;

    return new MenuItem({
        title: gettext_provider.gettext("Add or update image"),
        enable(state): boolean {
            return canInsert(state, node_type);
        },
        render: function (view: EditorView): HTMLElement {
            return buildImagePopover(
                popover_element_id,
                view,
                document,
                image_alt_id,
                image_src_id,
                image_popover_title_id,
                image_popover_submit_id,
                gettext_provider,
            );
        },
        run(): void {
            const title = document.getElementById(image_alt_id);
            if (title instanceof HTMLInputElement) {
                title.value = "";
            }
            const popover_href = document.getElementById(image_src_id);
            if (popover_href instanceof HTMLInputElement) {
                popover_href.value = "";
            }
        },
    });
}
