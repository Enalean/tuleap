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
import type { PullRequestInlineCommentPresenter } from "../../comments/PullRequestCommentPresenter";
import type { StorePullRequestCommentReplies } from "../../comments/PullRequestCommentRepliesStore";
import type { FileLine } from "../types";
import type { MapCommentWidgets } from "./FileDiffCommentWidgetsMap";
import { INLINE_COMMENT_POSITION_LEFT } from "../../comments/types";
import { isAnAddedLine, isAnUnmovedLine } from "../file-lines/file-line-helper";

export interface ScrollToFileDiffComment {
    scrollToUnifiedDiffComment: (
        comment_id_param: string | null,
        unidiff_codemirror: Editor
    ) => void;
    scrollToSideBySideDiffComment: (
        comment_id_param: string | null,
        left_codemirror: Editor,
        right_codemirror: Editor
    ) => void;
}

const getComment = (
    comments_store: StorePullRequestCommentReplies,
    comment_id: number
): PullRequestInlineCommentPresenter | null => {
    const comment =
        comments_store.getAllRootComments().find((comment) => comment.id === comment_id) ?? null;

    if (!comment || comment?.is_file_diff_comment === false) {
        return null;
    }

    return comment;
};

export const getLineNumberFromComment = (
    comment: PullRequestInlineCommentPresenter,
    file_lines: readonly FileLine[]
): number => {
    const line = file_lines[comment.unidiff_offset - 1];
    if (!line) {
        return 0;
    }

    if (isAnUnmovedLine(line)) {
        return comment.position === INLINE_COMMENT_POSITION_LEFT
            ? line.old_offset
            : line.new_offset;
    }

    return isAnAddedLine(line) ? line.new_offset : line.old_offset;
};

const scrollToCommentWidget = (
    comment_widgets_map: MapCommentWidgets,
    codemirror: Editor,
    comment_id: number,
    line_number: number
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
    comment_widgets_map: MapCommentWidgets
): ScrollToFileDiffComment => ({
    scrollToUnifiedDiffComment: (
        comment_id_param: string | null,
        unidiff_codemirror: Editor
    ): void => {
        if (comment_id_param === null) {
            return;
        }

        const comment_id = Number.parseInt(comment_id_param, 10);
        const comment = getComment(comments_store, comment_id);
        if (!comment) {
            return;
        }

        scrollToCommentWidget(
            comment_widgets_map,
            unidiff_codemirror,
            comment_id,
            comment.unidiff_offset
        );
    },
    scrollToSideBySideDiffComment: (
        comment_id_param: string | null,
        left_codemirror: Editor,
        right_codemirror: Editor
    ): void => {
        if (comment_id_param === null) {
            return;
        }

        const comment_id = Number.parseInt(comment_id_param, 10);
        const comment = getComment(comments_store, comment_id);
        if (!comment) {
            return;
        }

        scrollToCommentWidget(
            comment_widgets_map,
            comment.position === INLINE_COMMENT_POSITION_LEFT ? left_codemirror : right_codemirror,
            comment_id,
            getLineNumberFromComment(comment, file_lines)
        );
    },
});
