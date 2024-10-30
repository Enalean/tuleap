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

import type { EditorState } from "prosemirror-state";
import type { CheckIsMarkTypeRepeatedInSelection } from "../../../helpers/IsMarkTypeRepeatedInSelectionChecker";
import type { ExtractLinkProperties } from "../../../helpers/LinkPropertiesExtractor";
import { getWrappingNodeInfo } from "../helper/NodeInfoRetriever";
import { LinkState } from "./LinkState";

export type BuildLinkState = {
    build(state: EditorState): LinkState;
};

export const LinkStateBuilder = (
    check_is_mark_type_repeated_in_selection: CheckIsMarkTypeRepeatedInSelection,
    extract_link_properties: ExtractLinkProperties,
): BuildLinkState => ({
    build: (state: EditorState): LinkState => {
        const link_mark_type = state.schema.marks.link;

        if (
            check_is_mark_type_repeated_in_selection.isMarkTypeRepeatedInSelection(
                state,
                link_mark_type,
            )
        ) {
            return LinkState.disabled();
        }

        const link = extract_link_properties.extractLinkProperties(state.selection.anchor);
        if (link !== null) {
            return LinkState.forLinkEdition(link);
        }

        const { corresponding_node } = getWrappingNodeInfo(
            state.selection.$from,
            link_mark_type,
            state,
        );
        return LinkState.forLinkCreation(corresponding_node.textContent ?? "");
    },
});
