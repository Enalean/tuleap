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
import type { GetText } from "@tuleap/gettext";
import { MenuItem } from "prosemirror-menu";
import type { EditorView } from "prosemirror-view";
import { buildPopover } from "./popover-link";
import { updateInputValues } from "./input-value-updater";
import { getWrappingNodeInfo } from "../helper/NodeInfoRetriever";
import { schema } from "prosemirror-schema-basic";
import { isMarkTypeRepeatedInSelection } from "../../../helpers/is-mark-type-repeated-in-selection";
import type { EditorState } from "prosemirror-state";
import { removeSelectedLinks } from "../../link-popover/helper/remove-selected-links";
import type { CheckIsMArkActive } from "../helper/IsMarkActiveChecker";

export function linkItem(
    markType: MarkType,
    popover_element_id: string,
    gettext_provider: GetText,
    check_is_mark_active: CheckIsMArkActive,
): MenuItem {
    const link_title_id = `link-title-${popover_element_id}`;
    const link_href_id = `link-href-${popover_element_id}`;
    const popover_title_id = `popover-title-${popover_element_id}`;
    const popover_submit_id = `submit-popover-${popover_element_id}`;

    return new MenuItem({
        title: gettext_provider.gettext("Add or update link"),
        active(state): boolean {
            return check_is_mark_active.isMarkActive(state, markType);
        },
        enable: (state: EditorState): boolean =>
            !isMarkTypeRepeatedInSelection(state, state.schema.marks.link),
        render: function (view: EditorView): HTMLElement {
            return buildPopover(
                popover_element_id,
                view,
                document,
                link_title_id,
                link_href_id,
                popover_title_id,
                popover_submit_id,
                gettext_provider,
            );
        },
        run(state): void {
            updateInputValues(
                document,
                link_title_id,
                link_href_id,
                popover_title_id,
                popover_submit_id,
                gettext_provider,
                state,
            );
        },
    });
}

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
