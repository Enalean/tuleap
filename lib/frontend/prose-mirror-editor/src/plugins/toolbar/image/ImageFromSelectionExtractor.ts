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

import type { Selection } from "prosemirror-state";
import type { ImageProperties } from "../../../types/internal-types";
import { custom_schema } from "../../../custom_schema";
import type { FindEditorNodeAtPosition } from "../../../helpers/EditorNodeAtPositionFinder";

export type ExtractImageFromSelection = {
    extractImageProperties(selection: Selection): ImageProperties | null;
};

export const ImageFromSelectionExtractor = (
    find_node_at_position: FindEditorNodeAtPosition,
): ExtractImageFromSelection => ({
    extractImageProperties: (selection: Selection): ImageProperties | null => {
        if (selection.$head.pos - selection.$anchor.pos !== 1) {
            // When an image, and only an image is selected, then the selection's length is 1
            return null;
        }

        const selected_node = find_node_at_position.findNodeAtPosition(selection.from);
        if (!selected_node || selected_node.type !== custom_schema.nodes.image) {
            return null;
        }

        return {
            src: selected_node.attrs.src,
            title: selected_node.attrs.title,
        };
    },
});
