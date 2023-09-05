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

import type { Editor } from "codemirror";
import type { PullRequestInlineCommentPresenter } from "@tuleap/plugin-pullrequest-comments";
import type { InlineCommentWidget } from "../types";
import type { ScrollToFileDiffComment } from "./FileDiffCommentScroller";
import { FileDiffCommentScroller, getLineNumberFromComment } from "./FileDiffCommentScroller";
import { FileDiffCommentWidgetsMap } from "./FileDiffCommentWidgetsMap";
import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";
import { PullRequestCommentPresenterStub } from "../../../../tests/stubs/PullRequestCommentPresenterStub";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { PullRequestCommentRepliesStore } from "@tuleap/plugin-pullrequest-comments";
import {
    INLINE_COMMENT_POSITION_RIGHT,
    INLINE_COMMENT_POSITION_LEFT,
} from "@tuleap/plugin-pullrequest-constants";

type ScrollableEditor = Editor & {
    refresh: jest.SpyInstance;
    scrollIntoView: jest.SpyInstance;
};

const target_comment_id = 105;
const buildCommentWidget = (comment: PullRequestInlineCommentPresenter): InlineCommentWidget => {
    return FileDiffWidgetStub.buildInlineCommentWidget(160, {
        comment,
        scrollIntoView: jest.fn(),
    });
};

const buildScroller = (
    comment: PullRequestInlineCommentPresenter,
    comment_widget: InlineCommentWidget,
): ScrollToFileDiffComment => {
    const comments_store = PullRequestCommentRepliesStore([comment]);

    const comment_widgets_map = FileDiffCommentWidgetsMap();
    comment_widgets_map.addCommentWidget(comment_widget);

    return FileDiffCommentScroller(
        comments_store,
        [FileLineStub.buildUnMovedFileLine(1, 1, 1)],
        comment_widgets_map,
    );
};

describe("file-diff-comment-scroller", () => {
    let right_code_mirror: ScrollableEditor, left_code_mirror: ScrollableEditor;

    beforeEach(() => {
        jest.useFakeTimers();

        right_code_mirror = {
            refresh: jest.fn(),
            scrollIntoView: jest.fn(),
        } as unknown as ScrollableEditor;

        left_code_mirror = {
            refresh: jest.fn(),
            scrollIntoView: jest.fn(),
        } as unknown as ScrollableEditor;
    });

    describe("scrollToUnifiedDiffComment()", () => {
        it("Given a comment id and an editor, then it should scroll the widget displaying it into view", () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter(
                { unidiff_offset: 1 },
                target_comment_id,
            );
            const comment_widget = buildCommentWidget(comment);
            const scroller = buildScroller(comment, comment_widget);

            scroller.scrollToUnifiedDiffComment(target_comment_id, right_code_mirror);

            jest.advanceTimersByTime(1);

            expect(right_code_mirror.scrollIntoView).toHaveBeenCalledWith({
                line: comment.file.unidiff_offset - 1,
                ch: 0,
            });
            expect(right_code_mirror.refresh).toHaveBeenCalledTimes(1);
            expect(comment_widget.scrollIntoView).toHaveBeenCalledTimes(1);
        });
    });

    describe("scrollToSideBySideDiffComment()", () => {
        it(`Given a comment placed on the left side
            Then it should scroll the widget in the left codemirror into view`, () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter(
                {
                    unidiff_offset: 1,
                    position: INLINE_COMMENT_POSITION_LEFT,
                },
                target_comment_id,
            );
            const comment_widget = buildCommentWidget(comment);
            const scroller = buildScroller(comment, comment_widget);

            scroller.scrollToSideBySideDiffComment(
                target_comment_id,
                left_code_mirror,
                right_code_mirror,
            );

            jest.advanceTimersByTime(1);

            expect(left_code_mirror.scrollIntoView).toHaveBeenCalledWith({
                line: comment.file.unidiff_offset - 1,
                ch: 0,
            });
            expect(left_code_mirror.refresh).toHaveBeenCalledTimes(1);
            expect(comment_widget.scrollIntoView).toHaveBeenCalledTimes(1);
        });

        it(`Given a comment placed on the right side
            Then it should scroll the widget in the right codemirror into view`, () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter(
                {
                    unidiff_offset: 1,
                    position: INLINE_COMMENT_POSITION_RIGHT,
                },
                target_comment_id,
            );
            const comment_widget = buildCommentWidget(comment);
            const scroller = buildScroller(comment, comment_widget);

            scroller.scrollToSideBySideDiffComment(
                target_comment_id,
                left_code_mirror,
                right_code_mirror,
            );

            jest.advanceTimersByTime(1);

            expect(right_code_mirror.scrollIntoView).toHaveBeenCalledWith({
                line: comment.file.unidiff_offset - 1,
                ch: 0,
            });
            expect(right_code_mirror.refresh).toHaveBeenCalledTimes(1);
            expect(comment_widget.scrollIntoView).toHaveBeenCalledTimes(1);
        });
    });

    describe("getLineNumberFromComment()", () => {
        const file_lines = [
            FileLineStub.buildUnMovedFileLine(1, 1, 1),
            FileLineStub.buildUnMovedFileLine(2, 2, 2),
            FileLineStub.buildRemovedLine(3, 3),
            FileLineStub.buildRemovedLine(4, 4),
            FileLineStub.buildUnMovedFileLine(5, 3, 5),
            FileLineStub.buildUnMovedFileLine(6, 4, 6),
            FileLineStub.buildAddedLine(7, 7),
            FileLineStub.buildAddedLine(8, 8),
            FileLineStub.buildUnMovedFileLine(9, 9, 7),
        ];

        it("When the line is not found, it returns 0", () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter({
                unidiff_offset: 75,
            });

            expect(getLineNumberFromComment(comment, file_lines)).toBe(0);
        });

        it("When the comment is on an unmoved line on the left, then it should return the line's old_offset", () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter({
                unidiff_offset: 9,
                position: INLINE_COMMENT_POSITION_LEFT,
            });

            expect(getLineNumberFromComment(comment, file_lines)).toBe(7);
        });

        it("When the comment is on an unmoved line on the right, then it should return the line's new_offset", () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter({
                unidiff_offset: 5,
                position: INLINE_COMMENT_POSITION_RIGHT,
            });

            expect(getLineNumberFromComment(comment, file_lines)).toBe(3);
        });

        it("When the comment is on an added line, then it should return the line's new_offset", () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter({
                unidiff_offset: 7,
            });

            expect(getLineNumberFromComment(comment, file_lines)).toBe(7);
        });

        it("When the comment is on a removed line, then it should return the line's new_offset", () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter({
                unidiff_offset: 3,
            });

            expect(getLineNumberFromComment(comment, file_lines)).toBe(3);
        });
    });
});
