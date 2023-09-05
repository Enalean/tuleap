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

import type {
    FileDiffCommentWidget,
    FileDiffPlaceholderWidget,
    FileDiffWidget,
    InlineCommentWidget,
    NewInlineCommentFormWidget,
} from "../types";
import type { FileLineHandle, LineHandleWithWidgets } from "../types-codemirror-overriden";
import {
    PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
} from "@tuleap/plugin-pullrequest-comments";
import { TAG_NAME as PLACEHOLDER_NAME } from "./placeholders/FileDiffPlaceholder";

export function doesHandleHaveWidgets(handle: FileLineHandle): handle is LineHandleWithWidgets {
    return "widgets" in handle && Array.isArray(handle.widgets) && handle.widgets.length > 0;
}

export function isCodeCommentPlaceholderWidget(
    widget: FileDiffWidget,
): widget is FileDiffPlaceholderWidget {
    return isPlaceholderWidget(widget) && Boolean(widget.isReplacingAComment) === true;
}

export function isPlaceholderWidget(
    widget: FileDiffWidget | HTMLElement,
): widget is FileDiffPlaceholderWidget {
    return widget.localName === PLACEHOLDER_NAME;
}

export function isCommentWidget(
    widget: FileDiffWidget | HTMLElement,
): widget is FileDiffCommentWidget {
    return isANewInlineCommentWidget(widget) || isPullRequestCommentWidget(widget);
}

export function isPullRequestCommentWidget(
    widget: FileDiffWidget | HTMLElement,
): widget is InlineCommentWidget {
    return widget.localName === PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME;
}

export function isANewInlineCommentWidget(
    widget: FileDiffWidget | HTMLElement,
): widget is NewInlineCommentFormWidget {
    return widget.localName === PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME;
}
