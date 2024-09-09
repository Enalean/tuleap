/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { Plugin } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { removeLinkPopover, insertLinkPopover } from "./helper/link-popover-inserter";
import { getLinkValue } from "./helper/link-value-extractor";
import type { GetText } from "@tuleap/gettext";

export const initLinkPopoverPlugin = (gettext_provider: GetText, editor_id: string): Plugin =>
    new Plugin({
        props: {
            handleClick: (view: EditorView, pos: number): boolean => {
                removeLinkPopover(document, editor_id);

                let link_href = "";
                const dom_element = view.domAtPos(pos).node.parentElement;
                if (dom_element) {
                    link_href = dom_element.dataset.href ? dom_element.dataset.href : "";
                }

                if (!link_href) {
                    if (!view.state.selection.empty) {
                        return false;
                    }

                    link_href = getLinkValue(
                        view.state,
                        view.state.selection.from,
                        view.state.selection.to,
                    );
                }

                if (!link_href) {
                    return false;
                }

                const popover_anchor = view.domAtPos(pos).node.parentElement;
                if (!popover_anchor) {
                    return false;
                }

                insertLinkPopover(document, gettext_provider, popover_anchor, editor_id, link_href);

                return true;
            },
        },
    });
