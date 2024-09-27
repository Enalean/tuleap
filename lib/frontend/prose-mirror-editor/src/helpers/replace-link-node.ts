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

import type { LinkProperties } from "../types/internal-types";
import type { EditorView } from "prosemirror-view";
import { TextSelection } from "prosemirror-state";
import { getWrappingNodeInfo } from "../plugins/toolbar/helper/NodeInfoRetriever";

export function replaceLinkNode(view: EditorView, attrs: LinkProperties): void {
    const schema = view.state.schema;
    const wrapping_node_info = getWrappingNodeInfo(
        view.state.selection.$from,
        schema.marks.link,
        view.state,
    );

    if (!wrapping_node_info.is_creating_node) {
        const from = view.state.doc.resolve(wrapping_node_info.from);
        const to = view.state.doc.resolve(wrapping_node_info.to);
        view.dispatch(view.state.tr.setSelection(new TextSelection(from, to)));
    }

    const node = schema.text(attrs.title, [schema.marks.link.create(attrs)]);
    view.dispatch(view.state.tr.replaceSelectionWith(node, false));
    view.focus();
}
