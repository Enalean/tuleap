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

import { somethingIsEdited } from "./is-edited-checker";
import { submissionBarIsAlreadyActive } from "./submission-bar-status-checker";

const displaySubmissionBarIfNeeded = (
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_new_comment: HTMLElement | null,
    doc: Document,
): void => {
    if (
        !somethingIsEdited(
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
            doc,
        )
    ) {
        return;
    }

    const container = doc.querySelector(".tracker-artifact-submit-buttons-bar-container");
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.classList.add("tracker-artifact-submit-buttons-bar-container-display");
};

const removeSubmissionBarIfNeeded = (
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_new_comment: HTMLElement | null,
    doc: Document,
): void => {
    if (
        somethingIsEdited(
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
            doc,
        )
    ) {
        return;
    }

    const container = doc.querySelector(".tracker-artifact-submit-buttons-bar-container");
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.classList.remove("tracker-artifact-submit-buttons-bar-container-display");
};

export const toggleSubmitArtifactBar = (
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_new_comment: HTMLElement | null,
    doc: Document,
): void => {
    if (submissionBarIsAlreadyActive(document)) {
        removeSubmissionBarIfNeeded(
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
            doc,
        );
        return;
    }

    displaySubmissionBarIfNeeded(
        follow_up_comment_editor_instance,
        editor_format_selectbox,
        follow_up_new_comment,
        doc,
    );
};

export const toggleSubmissionBarForCommentInCkeditor = (
    doc: Document,
    follow_up_comment_editor_instance: CKEDITOR.editor | null,
    editor_format_selectbox: HTMLSelectElement | null,
    follow_up_new_comment: HTMLElement | null,
): void => {
    if (!follow_up_comment_editor_instance) {
        return;
    }
    follow_up_comment_editor_instance.on("change", () => {
        toggleSubmitArtifactBar(
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
            doc,
        );
    });

    follow_up_comment_editor_instance.on("input", () => {
        toggleSubmitArtifactBar(
            follow_up_comment_editor_instance,
            editor_format_selectbox,
            follow_up_new_comment,
            doc,
        );
    });
};
