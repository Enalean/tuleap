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

import type { GetText } from "@tuleap/gettext";
import type { EditorState } from "prosemirror-state";
import type { NodeInformation } from "../helper/NodeInfoRetriever";
import { getWrappingNodeInfo } from "../helper/NodeInfoRetriever";

function updateHrefAndReturnWhenALinkHaveBeenUpdated(
    state: EditorState,
    wrapping_node_info: NodeInformation,
    doc: Document,
    link_href_id: string,
): boolean {
    if (
        !state.doc.rangeHasMark(
            wrapping_node_info.from,
            wrapping_node_info.to,
            state.schema.marks.link,
        )
    ) {
        return false;
    }
    const popover_href = doc.getElementById(link_href_id);
    if (!(popover_href instanceof HTMLInputElement)) {
        return false;
    }
    state.doc.nodesBetween(wrapping_node_info.from, wrapping_node_info.to, (node) => {
        if (state.schema.marks.link.isInSet(node.marks)) {
            node.marks.forEach((mark) => {
                if (mark.type.name === "link") {
                    popover_href.value = mark.attrs.href;
                    return true;
                }
            });
        }
    });

    return false;
}

export function updateInputValues(
    doc: Document,
    link_title_id: string,
    link_href_id: string,
    popover_title_id: string,
    popover_submit_id: string,
    gettext_provider: GetText,
    state: EditorState,
): void {
    const wrapping_node_info = getWrappingNodeInfo(
        state.selection.$from,
        state.schema.marks.link,
        state,
    );

    const title = doc.getElementById(link_title_id);
    if (title instanceof HTMLInputElement) {
        title.value = title ? wrapping_node_info.corresponding_node.textContent : "";
    }
    const popover_href = doc.getElementById(link_href_id);
    if (popover_href instanceof HTMLInputElement) {
        popover_href.value = "";
    }

    const is_existing = updateHrefAndReturnWhenALinkHaveBeenUpdated(
        state,
        wrapping_node_info,
        doc,
        link_href_id,
    );

    const popover_title = doc.getElementById(popover_title_id);
    const submit_popover = doc.getElementById(popover_submit_id);

    if (!popover_title) {
        throw new Error("Popover title does not exists");
    }
    if (!submit_popover) {
        throw new Error("Popover submit does not exists");
    }
    if (is_existing) {
        popover_title.textContent = gettext_provider.gettext("Update link");
        submit_popover.textContent = gettext_provider.gettext("Update link");
    } else {
        popover_title.textContent = gettext_provider.gettext("Create a link");
        submit_popover.textContent = gettext_provider.gettext("Create a link");
    }
}
