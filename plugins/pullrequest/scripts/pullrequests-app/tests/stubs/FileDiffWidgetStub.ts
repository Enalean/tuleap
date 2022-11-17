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
} from "../../src/app/file-diff/diff-modes/types";

const base_element = document.implementation.createHTMLDocument().createElement("div");

const stubBounding = (height: number) => {
    return (): DOMRect => ({
        x: 0,
        y: 0,
        width: 500,
        height,
        right: 0,
        left: 500,
        top: 0,
        bottom: height,
        toJSON: (): string => JSON.stringify(""),
    });
};

export const FileDiffWidgetStub = {
    buildInlineCommentWidget: (height = 20): FileDiffCommentWidget => ({
        ...base_element,
        localName: "tuleap-pullrequest-comment",
        getBoundingClientRect: stubBounding(height),
    }),

    buildNewInlineCommentWidget: (height = 20): FileDiffCommentWidget => ({
        ...base_element,
        localName: "new-inline-comment",
        getBoundingClientRect: stubBounding(height),
    }),

    buildCodeCommentPlaceholder: (height = 20): FileDiffPlaceholderWidget => ({
        ...base_element,
        localName: "tuleap-pullrequest-placeholder",
        getBoundingClientRect: stubBounding(height),
        isReplacingAComment: true,
        height,
    }),

    buildCodePlaceholder: (height = 20): FileDiffPlaceholderWidget => ({
        ...base_element,
        localName: "tuleap-pullrequest-placeholder",
        getBoundingClientRect: stubBounding(height),
        isReplacingAComment: false,
        height,
    }),
};