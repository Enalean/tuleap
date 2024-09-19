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

import type { EditorNode } from "../../../types/internal-types";

export type FindPositionInDescendentsContainingReference = {
    findPositionsContainingReference(tree: EditorNode, reference: string): ReadonlyArray<number>;
};

export const PositionsInDescendentsFinder = (): FindPositionInDescendentsContainingReference => ({
    findPositionsContainingReference: (
        tree: EditorNode,
        reference: string,
    ): ReadonlyArray<number> => {
        const text_position_containing_reference: Array<number> = [];
        tree.descendants((child, pos) => {
            if (!child.isText) {
                return;
            }

            if (child.textContent.includes(reference)) {
                text_position_containing_reference.push(pos);
            }
        });

        return text_position_containing_reference;
    },
});
