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

import { getCommentPlaceholderWidget } from "./side-by-side-comment-placeholder-widget-finder";
import { FileDiffWidgetStub } from "../../../../../tests/stubs/FileDiffWidgetStub";
import { FileLineHandleStub } from "../../../../../tests/stubs/FileLineHandleStub";

describe("widget finder", () => {
    describe("getCommentPlaceholderWidget", () => {
        it("Given a handle, when it has no widgets, then it will return null", () => {
            const handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const comment_placeholder_widget = getCommentPlaceholderWidget(handle);

            expect(comment_placeholder_widget).toBeNull();
        });

        it("Given a handle with no comment placeholder, then it will return null", () => {
            const handle = FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildInlineCommentWidget(),
            ]);

            const comment_placeholder_widget = getCommentPlaceholderWidget(handle);

            expect(comment_placeholder_widget).toBeNull();
        });

        it("Given a handle with a comment placeholder, then it will return the comment placeholder widget", () => {
            const comment_placeholder = FileDiffWidgetStub.buildCodeCommentPlaceholder();
            const handle = FileLineHandleStub.buildLineHandleWithWidgets([comment_placeholder]);

            const comment_placeholder_widget = getCommentPlaceholderWidget(handle);

            expect(comment_placeholder_widget).toBe(comment_placeholder);
        });
    });
});
