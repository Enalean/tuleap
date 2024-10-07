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

import type { MarkType } from "prosemirror-model";
import { MenuItem } from "prosemirror-menu";
import { getWrappingNodeInfo } from "../helper/NodeInfoRetriever";
import { schema } from "prosemirror-schema-basic";
import { removeSelectedLinks } from "../../link-popover/helper/remove-selected-links";
import type { CheckIsMArkActive } from "../helper/IsMarkActiveChecker";

export function unlinkItem(
    markType: MarkType,
    popover_element_id: string,
    check_is_mark_active: CheckIsMArkActive,
): MenuItem {
    const unlink_icon_id = `unlink-icon-link-${popover_element_id}`;
    return new MenuItem({
        active(state): boolean {
            const icon = document.getElementById(unlink_icon_id);
            const wrapping_node_info = getWrappingNodeInfo(
                state.selection.$from,
                schema.marks.link,
                state,
            );
            const { from, to } = wrapping_node_info;
            if (!icon) {
                return false;
            }
            if (state.doc.rangeHasMark(from, to, markType)) {
                icon.removeAttribute("disabled");
                icon.classList.remove("prose-mirror-icon-disabled");
            } else {
                icon.setAttribute("disabled", "");
                icon.classList.add("prose-mirror-icon-disabled");
            }
            return check_is_mark_active.isMarkActive(state, markType);
        },
        render: function (): HTMLElement {
            const icon = document.createElement("i");
            icon.classList.add("fa-solid", "fa-unlink", "ProseMirror-icon");
            icon.id = unlink_icon_id;
            return icon;
        },
        run(state, dispatch, view, event): void {
            event.preventDefault();
            removeSelectedLinks(state, dispatch);
        },
    });
}
