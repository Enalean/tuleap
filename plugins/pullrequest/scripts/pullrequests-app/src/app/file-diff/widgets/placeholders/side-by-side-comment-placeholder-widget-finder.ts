/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { FileDiffPlaceholderWidget } from "../../types";
import type { FileLineHandle } from "../../types-codemirror-overriden";
import {
    doesHandleHaveWidgets,
    isCodeCommentPlaceholderWidget,
} from "../side-by-side-line-widgets-helper";

export function getCommentPlaceholderWidget(
    handle: FileLineHandle,
): FileDiffPlaceholderWidget | null {
    if (!doesHandleHaveWidgets(handle)) {
        return null;
    }

    const line_widgets = handle.widgets.map((line_widget) => line_widget.node);
    return (
        line_widgets.find((widget_element): widget_element is FileDiffPlaceholderWidget =>
            isCodeCommentPlaceholderWidget(widget_element),
        ) ?? null
    );
}
