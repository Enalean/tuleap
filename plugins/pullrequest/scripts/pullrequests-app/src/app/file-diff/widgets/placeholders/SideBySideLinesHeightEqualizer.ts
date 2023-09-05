/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { Editor } from "codemirror";
import { getCommentPlaceholderWidget } from "./side-by-side-comment-placeholder-widget-finder";
import {
    doesHandleHaveWidgets,
    isCommentWidget,
    isCodeCommentPlaceholderWidget,
} from "../side-by-side-line-widgets-helper";
import type { SynchronizedLineHandles } from "../../file-lines/SideBySideLineMapper";
import type { PositionPlaceholder } from "./SideBySidePlaceholderPositioner";
import type {
    FileLineHandle,
    LineWidgetWithNode,
    PlaceholderCreationParams,
} from "../../types-codemirror-overriden";
import type { FileDiffPlaceholderWidget } from "../../types";

export interface EqualizeLinesHeights {
    equalizeSides: (handles: SynchronizedLineHandles) => PlaceholderCreationParams | null;
}

export const SideBySideLinesHeightEqualizer = (
    left_code_mirror: Editor,
    right_code_mirror: Editor,
    positionner: PositionPlaceholder,
): EqualizeLinesHeights => ({
    equalizeSides: (handles: SynchronizedLineHandles): PlaceholderCreationParams | null => {
        const { left_handle, right_handle } = handles;
        const left_line_height = getTotalHeight(left_handle);
        const right_line_height = getTotalHeight(right_handle);

        if (left_line_height === right_line_height) {
            // nothing to do, all is already perfect
            return null;
        }

        if (left_line_height > right_line_height) {
            return adjustHeights(
                left_handle,
                left_line_height,
                left_code_mirror,
                right_handle,
                right_line_height,
                right_code_mirror,
                positionner,
            );
        }

        return adjustHeights(
            right_handle,
            right_line_height,
            right_code_mirror,
            left_handle,
            left_line_height,
            left_code_mirror,
            positionner,
        );
    },
});

function getSumOfWidgetsHeights(widgets: LineWidgetWithNode[]): number {
    return widgets
        .map((widget) => widget.node.getBoundingClientRect().height)
        .reduce((sum, value) => sum + value, 0);
}

function getTotalHeight(handle: FileLineHandle): number {
    if (!doesHandleHaveWidgets(handle)) {
        return 0;
    }

    const widgets = handle.widgets.filter(
        (widget) => isCommentWidget(widget.node) || isCodeCommentPlaceholderWidget(widget.node),
    );

    return getSumOfWidgetsHeights(widgets);
}

function getCommentsHeight(handle: FileLineHandle): number {
    if (!doesHandleHaveWidgets(handle)) {
        return 0;
    }

    const comments_widgets = handle.widgets.filter((widget) => isCommentWidget(widget.node));
    if (!comments_widgets.length) {
        return 0;
    }

    return getSumOfWidgetsHeights(comments_widgets);
}

function adjustPlaceholderHeight(
    placeholder: FileDiffPlaceholderWidget,
    widget_height: number,
): void {
    placeholder.height = Math.max(widget_height, 0);
}

function adjustHeights(
    handle: FileLineHandle,
    line_height: number,
    code_mirror: Editor,
    opposite_handle: FileLineHandle,
    opposite_line_height: number,
    opposite_code_mirror: Editor,
    positionner: PositionPlaceholder,
): PlaceholderCreationParams | null {
    const placeholder = getCommentPlaceholderWidget(handle);
    let optimum_height = opposite_line_height - getCommentsHeight(handle);

    if (!placeholder || optimum_height < 0) {
        const opposite_placeholder = getCommentPlaceholderWidget(opposite_handle);

        if (!opposite_placeholder) {
            optimum_height = line_height - getCommentsHeight(opposite_handle);

            const display_above_line = positionner.getDisplayAboveLineForWidget(handle);

            return {
                code_mirror: opposite_code_mirror,
                handle: opposite_handle,
                widget_height: optimum_height,
                display_above_line,
                is_comment_placeholder: true,
            };
        }

        optimum_height = line_height - getCommentsHeight(opposite_handle);

        adjustPlaceholderHeight(opposite_placeholder, optimum_height);

        return null;
    }

    adjustPlaceholderHeight(placeholder, optimum_height);

    return null;
}
