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
    CreateInlineCommentWidget,
    CreatePlaceholderWidget,
    CreateNewInlineCommentFormWidget,
} from "./side-by-side-code-mirror-widget-creator";
import type { PullRequestInlineCommentPresenter } from "../../comments/PullRequestCommentPresenter";
import type { FileLine } from "./types";
import type { FileLinesState } from "./side-by-side-lines-state";
import type { EqualizeLinesHeights } from "./side-by-side-line-height-equalizer";
import type { ManageCodeMirrorsContent } from "./side-by-side-code-mirrors-content-manager";
import type { InlineCommentPosition } from "../../comments/types";

import { isAnAddedLine, isARemovedLine } from "./file-line-helper";
import { INLINE_COMMENT_POSITION_LEFT } from "../../comments/types";
import { NewInlineCommentContext } from "../../comments/new-comment-form/NewInlineCommentContext";

export interface ManageCodeMirrorWidgetsCreation {
    displayInlineComment: (comment: PullRequestInlineCommentPresenter) => void;
    displayNewInlineCommentForm: (
        position: InlineCommentPosition,
        pull_request_id: number,
        file_path: string,
        line_number: number
    ) => void;
}

export const SideBySideCodeMirrorWidgetsCreationManager = (
    file_lines_state: FileLinesState,
    lines_equalizer: EqualizeLinesHeights,
    code_mirrors_content_manager: ManageCodeMirrorsContent,
    inline_comment_widget_creator: CreateInlineCommentWidget,
    new_inline_comment_form_widget_creator: CreateNewInlineCommentFormWidget,
    placeholder_widget_creator: CreatePlaceholderWidget
): ManageCodeMirrorWidgetsCreation => {
    const recomputeCommentPlaceholderHeight = (line: FileLine): void => {
        const line_handles = file_lines_state.getLineHandles(line);
        if (!line_handles) {
            return;
        }

        const placeholder_to_create = lines_equalizer.equalizeSides(line_handles);
        if (placeholder_to_create) {
            placeholder_widget_creator.displayPlaceholderWidget(placeholder_to_create);
        }
    };

    return {
        displayInlineComment: (comment: PullRequestInlineCommentPresenter): void => {
            const comment_line = file_lines_state.getCommentLine(comment);
            if (!comment_line) {
                return;
            }

            if (isARemovedLine(comment_line)) {
                inline_comment_widget_creator.displayInlineCommentWidget({
                    code_mirror: code_mirrors_content_manager.getLeftCodeMirrorEditor(),
                    comment,
                    line_number: comment_line.old_offset - 1,
                    post_rendering_callback: () => {
                        recomputeCommentPlaceholderHeight(
                            code_mirrors_content_manager.getLineInLeftCodeMirror(
                                comment_line.old_offset - 1
                            )
                        );
                    },
                });

                return;
            }

            if (isAnAddedLine(comment_line)) {
                inline_comment_widget_creator.displayInlineCommentWidget({
                    code_mirror: code_mirrors_content_manager.getRightCodeMirrorEditor(),
                    comment,
                    line_number: comment_line.new_offset - 1,
                    post_rendering_callback: () => {
                        recomputeCommentPlaceholderHeight(
                            code_mirrors_content_manager.getLineInRightCodeMirror(
                                comment_line.new_offset - 1
                            )
                        );
                    },
                });

                return;
            }

            const target_code_mirror =
                comment.position === INLINE_COMMENT_POSITION_LEFT
                    ? code_mirrors_content_manager.getLeftCodeMirrorEditor()
                    : code_mirrors_content_manager.getRightCodeMirrorEditor();
            const line_number =
                comment.position === INLINE_COMMENT_POSITION_LEFT
                    ? comment_line.old_offset - 1
                    : comment_line.new_offset - 1;

            inline_comment_widget_creator.displayInlineCommentWidget({
                code_mirror: target_code_mirror,
                comment,
                line_number,
                post_rendering_callback: () => {
                    recomputeCommentPlaceholderHeight(
                        comment.position === INLINE_COMMENT_POSITION_LEFT
                            ? code_mirrors_content_manager.getLineInLeftCodeMirror(line_number)
                            : code_mirrors_content_manager.getLineInRightCodeMirror(line_number)
                    );
                },
            });
        },
        displayNewInlineCommentForm: (
            position: InlineCommentPosition,
            pull_request_id: number,
            file_path: string,
            code_mirror_line_number: number
        ): void => {
            const line =
                position === INLINE_COMMENT_POSITION_LEFT
                    ? code_mirrors_content_manager.getLineInLeftCodeMirror(code_mirror_line_number)
                    : code_mirrors_content_manager.getLineInRightCodeMirror(
                          code_mirror_line_number
                      );

            if (!line) {
                return;
            }

            const code_mirror =
                position === INLINE_COMMENT_POSITION_LEFT
                    ? code_mirrors_content_manager.getLeftCodeMirrorEditor()
                    : code_mirrors_content_manager.getRightCodeMirrorEditor();

            new_inline_comment_form_widget_creator.displayNewInlineCommentFormWidget({
                code_mirror,
                line_number: code_mirror_line_number,
                context: NewInlineCommentContext.fromContext(
                    pull_request_id,
                    file_path,
                    line.unidiff_offset,
                    position
                ),
                post_rendering_callback: () => {
                    recomputeCommentPlaceholderHeight(line);
                },
            });
        },
    };
};
