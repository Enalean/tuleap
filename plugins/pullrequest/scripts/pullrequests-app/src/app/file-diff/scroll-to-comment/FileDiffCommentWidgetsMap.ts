/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { InlineCommentWidget } from "../types";

export interface MapCommentWidgets {
    addCommentWidget: (widget: InlineCommentWidget) => void;
    getCommentWidget: (comment_id: number) => InlineCommentWidget | null;
}

export const FileDiffCommentWidgetsMap = (): MapCommentWidgets => {
    const comment_widgets_map = new Map<number, InlineCommentWidget>();

    return {
        addCommentWidget: (widget: InlineCommentWidget): void => {
            comment_widgets_map.set(widget.comment.id, widget);
        },
        getCommentWidget: (comment_id: number): InlineCommentWidget | null => {
            return comment_widgets_map.get(comment_id) ?? null;
        },
    };
};
