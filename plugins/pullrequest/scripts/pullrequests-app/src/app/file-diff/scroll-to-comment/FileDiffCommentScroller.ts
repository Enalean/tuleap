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
import type {
    PullRequestInlineCommentPresenter,
    StorePullRequestCommentReplies,
} from "@tuleap/plugin-pullrequest-comments";
import type { FileLine } from "../types";
import type { MapCommentWidgets } from "./FileDiffCommentWidgetsMap";
import {
    INLINE_COMMENT_POSITION_LEFT,
    TYPE_INLINE_COMMENT,
} from "@tuleap/plugin-pullrequest-constants";
import { isAnAddedLine, isAnUnmovedLine } from "../file-lines/file-line-helper";

export interface ScrollToFileDiffComment {
    scrollToUnifiedDiffComment: (
        comment_id_param: number | null,
        unidiff_codemirror: Editor,
    ) => void;
    scrollToSideBySideDiffComment: (
        comment_id_param: number | null,
        left_codemirror: Editor,
        right_codemirror: Editor,
    ) => void;
}

const getComment = (
    comments_store: StorePullRequestCommentReplies,
    comment_id: number,
): PullRequestInlineCommentPresenter | null => {
    return (
        comments_store
            .getAllRootComments()
            .filter(
                (comment): comment is PullRequestInlineCommentPresenter =>
                    comment.type === TYPE_INLINE_COMMENT,
            )
            .find((comment) => comment.id === comment_id) ?? null
    );
};

export const getLineNumberFromComment = (
    comment: PullRequestInlineCommentPresenter,
    file_lines: readonly FileLine[],
): number => {
    const line = file_lines[comment.file.unidiff_offset - 1];
    if (!line) {
        return 0;
    }

    if (isAnUnmovedLine(line)) {
        return comment.file.position === INLINE_COMMENT_POSITION_LEFT
            ? line.old_offset
            : line.new_offset;
    }

    return isAnAddedLine(line) ? line.new_offset : line.old_offset;
};

const scrollToCommentWidget = (
    comment_widgets_map: MapCommentWidgets,
    codemirror: Editor,
    comment_id: number,
    line_number: number,
): void => {
    const widget = comment_widgets_map.getCommentWidget(comment_id);
    if (!widget) {
        return;
    }

    setTimeout(() => {
        codemirror.scrollIntoView({ line: line_number - 1, ch: 0 });
        codemirror.refresh();
        widget.scrollIntoView();
    });
};

export const FileDiffCommentScroller = (
    comments_store: StorePullRequestCommentReplies,
    file_lines: readonly FileLine[],
    comment_widgets_map: MapCommentWidgets,
): ScrollToFileDiffComment => ({
    scrollToUnifiedDiffComment: (comment_id: number | null, unidiff_codemirror: Editor): void => {
        if (comment_id === null) {
            return;
        }

        const comment = getComment(comments_store, comment_id);
        if (!comment) {
            return;
        }

        scrollToCommentWidget(
            comment_widgets_map,
            unidiff_codemirror,
            comment_id,
            comment.file.unidiff_offset,
        );
    },
    scrollToSideBySideDiffComment: (
        comment_id: number | null,
        left_codemirror: Editor,
        right_codemirror: Editor,
    ): void => {
        if (comment_id === null) {
            return;
        }

        const comment = getComment(comments_store, comment_id);
        if (!comment) {
            return;
        }

        scrollToCommentWidget(
            comment_widgets_map,
            comment.file.position === INLINE_COMMENT_POSITION_LEFT
                ? left_codemirror
                : right_codemirror,
            comment_id,
            getLineNumberFromComment(comment, file_lines),
        );
    },
});
