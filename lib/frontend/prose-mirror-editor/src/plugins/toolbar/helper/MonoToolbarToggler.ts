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

import { toggleMark } from "prosemirror-commands";
import type { EditorView } from "prosemirror-view";
import type { MarkType } from "prosemirror-model";

export type ToggleMark = {
    toggleMark(view: EditorView, mark_type: MarkType): void;
};
export const MarkToggle = (): ToggleMark => ({
    toggleMark(view: EditorView, mark_type: MarkType): void {
        toggleMark(mark_type)(view.state, view.dispatch, view);
    },
});
