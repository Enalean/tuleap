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

import { NAME as INLINE_COMMENT_NAME } from "../inline-comment-component";
import { NAME as NEW_INLINE_COMMENT_NAME } from "../new-inline-comment-component";
import { getDisplayAboveLineForWidget } from "./side-by-side-placeholder-positioner.js";
import { getCommentPlaceholderWidget } from "./side-by-side-widget-finder.js";

export function equalizeSides(left_code_mirror, right_code_mirror, handles) {
    if (typeof handles === "undefined") {
        // Do nothing
        return null;
    }

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
            right_code_mirror
        );
    }

    return adjustHeights(
        right_handle,
        right_line_height,
        right_code_mirror,
        left_handle,
        left_line_height,
        left_code_mirror
    );
}

function getNumberOfCommentsWidgets(handle) {
    if (hasNoWidgets(handle)) {
        return 0;
    }

    return handle.widgets.filter((widget) => isCommentWidget(widget)).length;
}

function getSumOfWidgetsHeights(widgets) {
    return widgets.map((widget) => widget.height).reduce((sum, value) => sum + value, 0);
}

function hasNoWidgets(handle) {
    return (
        !Object.prototype.hasOwnProperty.call(handle, "widgets") ||
        !handle.widgets ||
        (Object.prototype.hasOwnProperty.call(handle, "widgets") && handle.widgets.length === 0)
    );
}

function getTotalHeight(handle) {
    if (hasNoWidgets(handle)) {
        return 0;
    }

    const widgets = handle.widgets.filter(
        (widget) => isCommentWidget(widget) || isCommentPlaceholderWidget(widget)
    );

    return getSumOfWidgetsHeights(widgets);
}

function getCommentsHeight(handle) {
    if (hasNoWidgets(handle)) {
        return 0;
    }

    const comments_widgets = handle.widgets.filter((widget) => isCommentWidget(widget));

    if (!comments_widgets.length) {
        return 0;
    }

    return getSumOfWidgetsHeights(comments_widgets);
}

function isCommentWidget(line_widget) {
    return (
        line_widget.node.localName === NEW_INLINE_COMMENT_NAME ||
        line_widget.node.localName === INLINE_COMMENT_NAME
    );
}

function isCommentPlaceholderWidget(line_widget) {
    return line_widget.node.className.includes("pull-request-file-diff-comment-placeholder-block");
}

function adjustPlaceholderHeight(placeholder, widget_height) {
    const height = Math.max(widget_height, 0);
    placeholder.node.style.height = `${height}px`;

    placeholder.changed();
}

function adjustHeights(
    handle,
    line_height,
    code_mirror,
    opposite_handle,
    opposite_line_height,
    opposite_code_mirror
) {
    const placeholder = getCommentPlaceholderWidget(handle);
    let optimum_height = opposite_line_height - getCommentsHeight(handle);

    if (!placeholder || optimum_height < 0) {
        const opposite_placeholder = getCommentPlaceholderWidget(opposite_handle);

        if (!opposite_placeholder) {
            optimum_height = line_height - getCommentsHeight(opposite_handle);

            const display_above_line = getDisplayAboveLineForWidget(handle);

            return {
                code_mirror: opposite_code_mirror,
                handle: opposite_handle,
                widget_height: optimum_height,
                display_above_line,
                is_comment_placeholder: true,
            };
        }

        if (haveSameNumberOfCommentWidgets(handle, opposite_handle)) {
            return minimizePlaceholders(handle, opposite_handle);
        }

        optimum_height = line_height - getCommentsHeight(opposite_handle);

        return adjustPlaceholderHeight(opposite_placeholder, optimum_height);
    }

    if (haveSameNumberOfCommentWidgets(handle, opposite_handle)) {
        return minimizePlaceholders(handle, opposite_handle);
    }

    return adjustPlaceholderHeight(placeholder, optimum_height);
}

function haveSameNumberOfCommentWidgets(handle, opposite_handle) {
    const nb_comments = getNumberOfCommentsWidgets(handle);
    const nb_opposite_comments = getNumberOfCommentsWidgets(opposite_handle);

    return nb_comments === nb_opposite_comments;
}

function minimizePlaceholders(handle, opposite_handle) {
    const left_placeholder = getCommentPlaceholderWidget(handle);
    const right_placeholder = getCommentPlaceholderWidget(opposite_handle);

    if (left_placeholder) {
        adjustPlaceholderHeight(left_placeholder, 0);
    }

    if (right_placeholder) {
        adjustPlaceholderHeight(right_placeholder, 0);
    }
}
