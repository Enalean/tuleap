/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import {
    toggleSubmissionBarForCommentInCkeditor,
    toggleSubmitArtifactBar,
} from "./submission-bar-toggler";

function bindSubmissionBarToFollowUpComment(
    doc: Document,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    follow_up_new_comment: HTMLElement | null,
): void {
    if (!follow_up_new_comment) {
        return;
    }

    follow_up_new_comment.addEventListener("input", () => {
        toggleSubmitArtifactBar(
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
            doc,
        );
    });
}

function bindSubmissionBarToEditorFormatSelectBox(
    doc: Document,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    follow_up_new_comment: HTMLElement | null,
): void {
    if (!editor_format_selectbox) {
        return;
    }
    editor_format_selectbox.addEventListener("change", () => {
        toggleSubmissionBarForCommentInCkeditor(
            doc,
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
        );
    });
}

export const bindSubmissionBarToFollowups = (
    doc: Document,
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_new_comment: HTMLElement | null,
): void => {
    toggleSubmissionBarForCommentInCkeditor(
        doc,

        follow_up_comment_editor_instance,
        editor_format_selectbox,
        follow_up_new_comment,
    );

    bindSubmissionBarToFollowUpComment(
        doc,
        editor_format_selectbox,
        follow_up_comment_editor_instance,
        follow_up_new_comment,
    );

    bindSubmissionBarToEditorFormatSelectBox(
        doc,
        editor_format_selectbox,
        follow_up_comment_editor_instance,
        follow_up_new_comment,
    );
};
