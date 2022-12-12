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

import { FileDiffCommentWidgetsMap } from "./file-diff-comment-widgets-map";
import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";
import { PullRequestCommentPresenterStub } from "../../../../tests/stubs/PullRequestCommentPresenterStub";

describe("file-diff-comment-widgets-map", () => {
    it("should return the widget associated to a comment id", () => {
        const widget_mapper = FileDiffCommentWidgetsMap();
        const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter({
            id: 105,
        });
        const comment_widget = FileDiffWidgetStub.buildInlineCommentWidget(20, {
            comment,
        });

        widget_mapper.addCommentWidget(comment_widget);

        expect(widget_mapper.getCommentWidget(comment.id)).toStrictEqual(comment_widget);
    });
});
