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
import type { EditorState } from "prosemirror-state";
export type CheckIsMArkActive = {
    isMarkActive(state: EditorState, type: MarkType): boolean;
};
export const IsMarkActiveChecker = (): CheckIsMArkActive => ({
    isMarkActive(state: EditorState, type: MarkType): boolean {
        const { from, $from, to, empty } = state.selection;
        if (empty) {
            return Boolean(type.isInSet(state.storedMarks || $from.marks()));
        }
        return state.doc.rangeHasMark(from, to, type);
    },
});
