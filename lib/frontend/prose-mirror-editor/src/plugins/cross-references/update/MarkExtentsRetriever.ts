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

import type { MarkType } from "prosemirror-model";
import type { FindEditorNodeAtPosition } from "../../../helpers/EditorNodeAtPositionFinder";
import type { Extents } from "../../../types/internal-types";

export type RetrieveMarkExtents = {
    retrieveExtentsOfMarkAtPosition(mark_type: MarkType, position: number): Extents | null;
};

export const MarkExtentsRetriever = (
    find_node_at_position: FindEditorNodeAtPosition,
): RetrieveMarkExtents => {
    const doesMarkExistsAtPosition = (mark_type: MarkType, position: number): boolean => {
        const node = find_node_at_position.findNodeAtPosition(position);
        if (!node) {
            return false;
        }
        return mark_type.isInSet(node.marks) !== undefined;
    };

    return {
        retrieveExtentsOfMarkAtPosition: (mark_type, position): Extents | null => {
            if (!doesMarkExistsAtPosition(mark_type, position)) {
                return null;
            }

            let from = position;
            while (doesMarkExistsAtPosition(mark_type, from)) {
                from--;
            }

            let to = position;
            while (doesMarkExistsAtPosition(mark_type, to)) {
                to++;
            }

            return { from: from + 1, to: to - 1 };
        },
    };
};
