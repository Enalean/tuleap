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

import type { LineWidgetOptions } from "codemirror";
import type { FileLineHandle, CommentWidgetCreationParams } from "../types-codemirror-overriden";
import {
    doesHandleHaveWidgets,
    isCodeCommentPlaceholderWidget,
} from "./side-by-side-line-widgets-helper";

const getPlaceholderWidgetIndex = (handle: FileLineHandle): number => {
    if (!doesHandleHaveWidgets(handle)) {
        return -1;
    }

    return handle.widgets.findIndex((widget_element) =>
        isCodeCommentPlaceholderWidget(widget_element.node),
    );
};

export const getWidgetPlacementOptions = (
    comment_widget_params: CommentWidgetCreationParams,
): LineWidgetOptions => {
    const line_handle = comment_widget_params.code_mirror.getLineHandle(
        comment_widget_params.line_number,
    );
    if (!line_handle) {
        return {};
    }

    const placeholder_index = getPlaceholderWidgetIndex(line_handle);
    if (placeholder_index !== -1) {
        return {
            coverGutter: true,
            insertAt: getPlaceholderWidgetIndex(line_handle),
        };
    }

    return {
        coverGutter: true,
    };
};
